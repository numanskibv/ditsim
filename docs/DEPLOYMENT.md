# Installatie & overdracht — Datacenter Simulator

Deze gids is voor de docent/beheerder die de simulator **host** (lokaal, op een
eigen server met Docker, of in de cloud). Voor het *gebruik* van de app, zie de
[rolhandleidingen](handleidingen/README.md) — die zijn ook in de app te lezen en
te printen (docent → **Handleidingen**).

---

## In het kort

| Scenario | Database | Hoe |
|---|---|---|
| Even lokaal proberen / lesgeven op één pc | SQLite | `composer run dev` |
| Alles-in-één op een server | PostgreSQL (in container) | `docker compose up --build` |
| Cloud (Laravel Cloud, container-platform, VPS) | Managed Postgres/MySQL | env instellen + processen draaien (zie onder) |

De code werkt op **SQLite, PostgreSQL en MySQL** zonder aanpassing — alleen de
omgevingsvariabelen verschillen.

---

## 1. Lokaal (SQLite) — snelst om te proberen

Vereist: PHP 8.3, Composer, Node 22+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # of: php artisan app:install
composer run dev                # server + queue + logs + assets
```

Open http://localhost:8000. Voor realtime updates daarnaast `php artisan reverb:start`.
Demo-accounts: zie [handleidingen/README.md](handleidingen/README.md) (wachtwoord `password`).

---

## 2. Eén server met Docker Compose (PostgreSQL) — aanrader voor een klas

Vereist: alleen Docker.

```bash
docker compose up --build
```

Dit start **alles**: PostgreSQL, de webserver, de queue-worker, de scheduler en
Reverb. De database staat in een persistent volume (`pgdata`) en overleeft
herstarts. Bij de eerste keer worden migraties + demodata automatisch geladen
(`app:install`, idempotent — een tweede start seedt niet opnieuw).

- App: http://localhost:8000 · Reverb: ws://localhost:8080
- **Belangrijk:** zet vóór een publieke inzet een sterk wachtwoord via
  `DEFAULT_ACCOUNT_PASSWORD` in `docker-compose.yml` (en `APP_DEBUG: "false"`,
  `APP_ENV: production`, een eigen `APP_KEY`).

---

## 3. Cloud (managed database)

In de cloud is een **managed Postgres of MySQL** sterk aanbevolen: het filesystem
is daar vaak vluchtig (SQLite-data zou verloren gaan) en een echte DB verdraagt
een hele klas tegelijk.

### 3.1 Omgevingsvariabelen

| Variabele | Waarde | Toelichting |
|---|---|---|
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | nooit `true` in productie |
| `APP_KEY` | `base64:…` | genereer met `php artisan key:generate --show` |
| `APP_URL` | `https://…` | de publieke URL |
| `DB_CONNECTION` | `pgsql` of `mysql` | |
| `DB_HOST` / `DB_PORT` | van je provider | pgsql=5432, mysql=3306 |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | van je provider | |
| `SESSION_DRIVER` | `database` | |
| `QUEUE_CONNECTION` | `database` | (of `redis`) |
| `CACHE_STORE` | `database` | (of `redis`) |
| `DEFAULT_ACCOUNT_PASSWORD` | **sterk wachtwoord** | wordt het wachtwoord van de demo-/studentaccounts |
| `SEED_DEMO` | `true` / `false` | `false` = schoon starten zonder demo-accounts; de **eerste registratie wordt dan automatisch de docent** |
| `RUN_MIGRATIONS` | `1` | alleen op het web-/release-proces (zie 3.3) |
| `BROADCAST_CONNECTION` | `reverb` | optioneel; zonder Reverb pollt het dashboard |
| `REVERB_APP_ID/KEY/SECRET` | eigen waarden | als je Reverb gebruikt |

Voor realtime in de browser worden de `VITE_REVERB_*` waarden **tijdens de build**
ingebakken (zie de `ARG`’s in de [Dockerfile](../Dockerfile)). Gebruik je een
eigen domein voor websockets, bouw dan met de juiste `VITE_REVERB_HOST/PORT/SCHEME`.

### 3.2 Processen die moeten draaien

Dezelfde image/codebase draait alle rollen; start ze als aparte processen:

| Proces | Commando | Nodig voor |
|---|---|---|
| Web | `php artisan serve --host=0.0.0.0 --port=8000` (of PHP-FPM/Nginx/Octane) | de app |
| Queue | `php artisan queue:work --tries=3` | scenario's met vertraging + broadcasts |
| Scheduler | `php artisan schedule:work` (of cron `* * * * * php artisan schedule:run`) | automatische `simulate:tick` |
| Reverb | `php artisan reverb:start --host=0.0.0.0 --port=8080` | realtime dashboard (optioneel) |

> `php artisan serve` volstaat voor een klas. Voor zwaardere belasting kun je
> PHP-FPM + Nginx of Laravel Octane gebruiken.

### 3.3 Eerste deploy / migraties

De [docker-entrypoint.sh](../docker-entrypoint.sh) draait bij `RUN_MIGRATIONS=1`
automatisch `php artisan app:install`: dat **migreert altijd** en **seedt alleen
een lege database** (veilig bij elke deploy, ook op managed DB). Draai je zonder
de entrypoint, voer dan handmatig uit:

```bash
php artisan app:install --no-interaction
```

**Schoon starten zonder demo (aanrader voor productie):** zet `SEED_DEMO=false`. Dan worden
er geen demo-accounts klaargezet en wordt de **eerste registratie automatisch de docent
(beheerder)**. De docent claimt zo het systeem met een eigen e-mail en wachtwoord; daarna
zijn alle volgende registraties gewone klant-accounts en maakt de docent de studenten aan via
**Studenten beheren**.

---

## 4. Beheer & operatie

- **Wachtwoorden:** laat elke gebruiker zijn wachtwoord wijzigen via
  **Instellingen → Beveiliging**. Het docent-account zou je sowieso direct na de
  eerste login een eigen wachtwoord moeten geven.
- **Omgeving resetten voor een nieuwe klas:** in de app via docent →
  **Studenten beheren → Volledige reset…** (typ `RESET`), of op de server met
  `php artisan simulate:reset`. Beide wissen álle data en zetten de demo terug.
- **Studenten klaarzetten:** docent → **Studenten beheren**: accounts aanmaken,
  koppels maken, en per student een startscenario toewijzen.
- **Back-ups:** gebruik de back-upfunctie van je managed database. (Bij de
  Docker-Compose-variant: back-up het `pgdata`-volume.)

---

## 5. Problemen oplossen

| Symptoom | Oplossing |
|---|---|
| `could not find driver` (DB) | DB-extensie ontbreekt; de meegeleverde image bevat pdo_sqlite/pdo_mysql/pdo_pgsql. Bij eigen image: installeer de juiste `pdo_*`. |
| Lege app / `no such table` | Migraties niet gedraaid → `php artisan app:install`. |
| Scenario met vertraging gebeurt niet | Queue-worker draait niet → `php artisan queue:work`. |
| Metrics veranderen niet vanzelf | Scheduler draait niet → `php artisan schedule:work`. |
| Dashboard niet realtime | Reverb draait niet of `VITE_REVERB_*` mismatcht de publieke host. Zonder Reverb ververst het dashboard periodiek. |
| Assets-fout (Vite manifest) | `npm run build` (gebeurt in de Docker-build). |

---

## 6. Tests

```bash
php artisan test
```

De volledige suite hoort groen te zijn. Tests draaien op een eigen (SQLite)
in-memory/bestand-database en raken je echte data niet.
