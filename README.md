# Datacenter Simulator

Een Laravel-webapplicatie die een **datacenter-werkomgeving** nabootst, zodat MBO-niveau-4
studenten (keuzedeel **Datacenter IT techniek, K1171**) de zes praktijkopdrachten kunnen
uitvoeren en daarmee **verifieerbaar examenbewijs** opbouwen — ook als echte praktijkmiddelen
ontbreken.

Kernprincipe: **elk scherm levert bewijs op** (screenshot/PDF) dat een beoordelingscriterium
afdekt. Elke handeling logt automatisch tijdstip en naam van de uitvoerder.

## Belangrijkste functies

- **Geïsoleerde werelden per student** — elke student-technicus werkt in een eigen,
  afgeschermde omgeving; meerdere studenten (bv. 7 koppels) werken parallel zonder elkaar te
  storen.
- **Koppels / tegenrollen** — binnen een koppel handelt de partner als
  leidinggevende/klant in elkaars wereld (vier-ogen blijft gewaarborgd), zonder extra accounts.
- **Zes modules** die de opdrachten afdekken: DCIM (racks/devices), tickets (met SLA en
  vier-ogen), monitoring/NOC (realtime), installatieplan-bouwer (PDF), toegangsregister,
  inspectierondes, communicatielog en **portfolio-bewijs (PDF-export)**.
- **Docent-bediening** — studenten beheren, koppelen, een **bibliotheek van 15 scenario's**
  per student toewijzen, storingen triggeren, en de omgeving resetten / demodata
  verwijderen of importeren. Plus de handleidingen in de app (lezen & printen).
- **Rollen**: technicus (student), leidinggevende, klant, docent (beheer).

## Snel starten (lokaal, SQLite)

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

## Alles-in-één met Docker (PostgreSQL)

```bash
docker compose up --build
```

Start db (Postgres) + webserver + queue + scheduler + Reverb. De stack start **schoon**
(`SEED_DEMO=false`): registreer het eerste account via de app — dat wordt automatisch de
**docent**, die daarna de studenten aanmaakt. Open http://localhost:8000.

> Wil je in plaats daarvan de **MediCloud-demo met kant-en-klare accounts**? Zet
> `SEED_DEMO=true` (in `docker-compose.yml`, of `php artisan migrate --seed` lokaal).

## Demo-accounts (alleen met `SEED_DEMO=true` / `migrate --seed`)

Wachtwoord standaard `password` (instelbaar via `DEFAULT_ACCOUNT_PASSWORD`).

| Rol | E-mail |
|-----|--------|
| Docent | `docent@datacenter-sim.test` |
| Technicus (student) | `technicus@datacenter-sim.test` · `sanne@…` · `sven@…` |
| Leidinggevende | `leidinggevende@datacenter-sim.test` |
| Klant | `klant@medicloud.test` |

## Documentatie

- **Rolhandleidingen** (ook in de app te lezen/printen): [docs/handleidingen/](docs/handleidingen/README.md)
- **Installatie & overdracht** (lokaal, Docker, cloud): [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)
- **Testscript** (acceptatie-doorloop met een student): [docs/TESTSCRIPT.md](docs/TESTSCRIPT.md)

## Techniek

Laravel 13 · PHP 8.3 · Livewire 4 + Flux · Tailwind v4 · Laravel Reverb (websockets) ·
barryvdh/laravel-dompdf · Pest. Database: SQLite (lokaal) of PostgreSQL/MySQL (cloud).

## Tests

```bash
php artisan test
```

## Handige beheercommando's

| Commando | Doel |
|---|---|
| `php artisan app:install` | Migreren + demodata seeden als de database leeg is (idempotent) |
| `php artisan simulate:tick` | Eén monitoring-tick (metrics) — `--student=<id>` voor één wereld |
| `php artisan simulate:reset` | Volledige reset naar fabrieksinstelling |
| `php artisan simulate:clear` | Alle wereld-/demodata wissen; accounts en scenario's behouden |
| `php artisan simulate:import-demo` | MediCloud-demo (opnieuw) klaarzetten om te testen |
