# Handleiding — Docent (uitgebreid)

> **Wie ben jij?** Je bent de docent én beheerder van de Datacenter Simulator. Jij richt de
> leeromgeving in, maakt studentaccounts aan, wijst startscenario's toe, triggert storingen,
> houdt toezicht op het werk en controleert het examenbewijs. Je hebt inzage in **alles**.

**Inloggen:** `docent@datacenter-sim.test` / `password`

---

## Inhoud

1. [In het kort (cheatsheet)](#1-in-het-kort-cheatsheet)
2. [De simulator starten](#2-de-simulator-starten)
3. [Inloggen & demo-accounts](#3-inloggen--demo-accounts)
4. [De vier rollen en hun rechten](#4-de-vier-rollen-en-hun-rechten)
5. [Kernconcept: elke student een eigen, geïsoleerde wereld](#5-kernconcept-elke-student-een-eigen-geïsoleerde-wereld)
6. [Studenten beheren](#6-studenten-beheren)
7. [Scenario's: storingen en startopstellingen](#7-scenarios-storingen-en-startopstellingen)
8. [Monitoring & "Simuleer tick"](#8-monitoring--simuleer-tick)
9. [Acteren als gedeelde rol (leidinggevende/klant) en koppels](#9-acteren-als-gedeelde-rol-leidinggevendeklant-en-koppels)
10. [Toezicht houden en beoordelen](#10-toezicht-houden-en-beoordelen)
11. [Een complete oefenronde opzetten](#11-een-complete-oefenronde-opzetten)
12. [Realtime updates (Reverb) voor de demo](#12-realtime-updates-reverb-voor-de-demo)
13. [Veelvoorkomende handelingen (cheatsheet)](#13-veelvoorkomende-handelingen-cheatsheet)
14. [Problemen oplossen](#14-problemen-oplossen)
15. [Aandachtspunten en beperkingen](#15-aandachtspunten-en-beperkingen)

---

## 1. In het kort (cheatsheet)

| Ik wil… | Doe dit |
|---|---|
| Verse, gevulde lesomgeving (terminal) | `php artisan migrate:fresh --seed` |
| Volledige reset zónder terminal (cloud) | **Studenten beheren** → **Volledige reset…** → typ `RESET` |
| Alleen demodata weg (accounts/scenario's behouden) | **Studenten beheren** → **Demodata verwijderen…** |
| Demodata terugzetten om te testen | **Studenten beheren** → **Demodata importeren…** |
| Student een startwereld geven uit de bibliotheek | **Studenten beheren** → kies een scenario *01…15* → **Toewijzen & starten** |
| App draaien | `composer run dev` (server + queue + logs + assets) |
| Realtime dashboard | Daarnaast `php artisan reverb:start` |
| Student toevoegen | Menu **Studenten beheren** → naam + e-mail → **Toevoegen** |
| Student een startwereld geven | **Studenten beheren** → kies **Startscenario** → **Toewijzen & starten** |
| Wereld van een student leegmaken/herstarten | **Studenten beheren** → **Reset** |
| Eén apparaat direct laten storen | Menu **Scenario's beheren** → Directe status → **Direct toepassen** |
| Een storing geleidelijk laten oplopen | **Scenario's beheren** → scenario met vertraging → **Start** |
| Tijd vooruitspoelen (metrics) | Menu **Monitoring** → **Simuleer tick** |
| Bewijs van een student controleren | Kies de student als **Actieve student** → **Portfoliobewijs** |

---

## 2. De simulator starten

De simulator is een Laravel-webapplicatie (PHP 8.3, SQLite). Je hebt een terminal nodig in
de projectmap.

### 2.1 Eenmalig / bij een nieuwe klas: database vullen

```bash
php artisan migrate:fresh --seed
```

Dit zet de database opnieuw op met realistische demodata: klant **MediCloud BV**, datacenter
**DC-Utrecht**, rack **R03** met vier apparaten, voorbeeldtickets, een inspectierapport,
bezoekers en twee scenario's. Alle demo-accounts krijgen het wachtwoord `password`.

> ⚠️ `migrate:fresh` **wist alle data**. Gebruik dit alleen om opnieuw te beginnen, niet
> midden in een les.

### 2.2 De app draaien (ontwikkel-/lesmodus)

```bash
composer run dev
```

Dit start in één keer: de webserver (`php artisan serve`), de **queue-worker** (nodig voor
scenario's met vertraging), de logviewer en de asset-build. De app draait dan op
`http://localhost:8000`.

Wil je liever losse processen, dan zijn de kerncommando's:

```bash
php artisan serve            # webserver
php artisan queue:listen     # verwerkt vertraagde scenario-acties
php artisan schedule:work    # draait elke minuut 'simulate:tick' (alle werelden)
npm run dev                  # frontend assets
```

> 💡 De automatische tick (`Schedule::command('simulate:tick')->everyMinute()`) loopt alleen
> als `php artisan schedule:work` draait. Doe je dat niet, dan gebeuren metric-veranderingen
> pas wanneer iemand op **Simuleer tick** klikt — voor de les is dat meestal prima.

---

## 3. Inloggen & demo-accounts

Open de URL (lokaal `http://localhost:8000`) en log in. Wachtwoord overal: **`password`**.

| Rol | Naam | E-mail | Bijzonderheid |
|---|---|---|---|
| Docent | Dirk Docent | `docent@datacenter-sim.test` | Beheert scenario's & studenten, ziet alles |
| Technicus (student) | Tessa Technicus | `technicus@datacenter-sim.test` | Heeft de gevulde demo-wereld (R03) |
| Technicus (student) | Sanne Student | `sanne@datacenter-sim.test` | Start **leeg** |
| Technicus (student) | Sven Student | `sven@datacenter-sim.test` | Start **leeg** |
| Leidinggevende | Laura Leidinggevende | `leidinggevende@datacenter-sim.test` | Keurt plannen/tickets goed (vier-ogen) |
| Klant | MediCloud BV | `klant@medicloud.test` | Maakt meldingen, bezoekt het datacenter |

Voor een echte klas maak je per student een eigen technicus-account aan (zie
[§6](#6-studenten-beheren)). De seed-studenten Sanne en Sven zijn vooral demonstratie-
materiaal.

---

## 4. De vier rollen en hun rechten

De simulator kent vier rollen (kolom `role` op de gebruiker):

| Rol | Mag | Menu-extra |
|---|---|---|
| **technicus** | De opdrachten uitvoeren: DCIM, tickets afhandelen, inspecties, toegangsregister, monitoring-tick | "Opdrachten uitvoeren" |
| **leidinggevende** | Installatieplannen en tickets **goedkeuren/afkeuren** en aftekenen (vier-ogen) | "Goedkeuringen" |
| **klant** | **Meldingen** (tickets) maken; bezoek aankondigen | "Melding maken" |
| **docent** | Scenario's beheren, studenten beheren, alles inzien | "Scenario's beheren", "Studenten beheren" |

De gedeelde modules (Dashboard, Racks, Tickets, Monitoring, Toegangsregister,
Inspectierondes, Berichten, Portfoliobewijs) zijn voor iedereen zichtbaar; wat je er kúnt
doen hangt af van je rol en van de **actieve wereld** (zie [§5](#5-kernconcept-elke-student-een-eigen-geïsoleerde-wereld) en [§9](#9-acteren-als-gedeelde-rol-leidinggevendeklant)).

---

## 5. Kernconcept: elke student een eigen, geïsoleerde wereld

Dit is het belangrijkste om te begrijpen.

- **Elke student (technicus) werkt in een eigen, afgeschermde simulatie-omgeving.** Racks,
  apparaten, tickets, alarmen, inspectierapporten, bezoekers, berichten en installatieplannen
  zijn per student gescheiden. Student A ziet de apparatuur of tickets van student B **niet**.
- Een **technicus** ziet automatisch alleen zijn eigen wereld; hij hoeft niets te kiezen.
- De **demodata** (R03 met MediCloud-apparatuur) hoort bij de demo-technicus **Tessa**. Een
  nieuw aangemaakte student start daarom met een **lege** omgeving — totdat jij er een
  startscenario aan toewijst.
- De rollen **docent, leidinggevende en klant** zijn *gedeeld*: zij kunnen in de wereld van
  een specifieke student handelen door bovenin **Actieve student** te kiezen (zie [§9](#9-acteren-als-gedeelde-rol-leidinggevende-klant-en-koppels)).
- **Koppels (tegenrollen).** Je kunt twee studenten aan elkaar koppelen. Binnen een koppel
  (X, Y) handelt Y als **leidinggevende/klant** in de wereld van X, en X in die van Y — zónder
  aparte accounts. In je eigen wereld ben je technicus (je voert uit); in de partnerwereld mag
  je goedkeuren en meldingen maken, maar niet zelf technisch werk doen. Zo blijft de
  vier-ogen-eis overeind. Koppelen doe je op **Studenten beheren** (zie §6.3).

Praktisch betekent dit dat 7 koppels (14 studenten) tegelijk, parallel en zonder elkaar te
storen in dezelfde simulator kunnen werken — en elkaars werk aftekenen.

---

## 6. Studenten beheren

**Menu → Studenten beheren** (alleen zichtbaar voor de docent).

Op dit scherm zie je alle studenten (technici) met per student het aantal apparaten in hun
wereld, hun toegewezen scenario en hun partner.

### 6.1 Een student toevoegen

1. Vul bij **Student toevoegen** een **naam**, **studentnummer** en **e-mail** in.
2. Klik **Toevoegen**.
3. Het account krijgt automatisch het wachtwoord **`password`** en de rol technicus.

> Het **studentnummer** staat naast de naam in de lijst (en is daar ook achteraf aan te
> passen via het invoerveld + ✓). **Naam én studentnummer komen in de bestandsnaam van het
> geëxporteerde portfoliobewijs**, bv. `portfoliobewijs-opdracht-1-jan-jansen-s123456.pdf`,
> en bovenaan op de PDF zelf.

### 6.2 Een startscenario toewijzen

1. Kies bij de student in de kolom **Startscenario** een scenario uit de lijst. Er staat een
   **bibliotheek van 15 kant-en-klare scenario's** klaar (genummerd *01 … 15*), bv.
   *"02 · Webserver loopt warm (predictief)"* of *"03 · Database in storing"* — elk met een
   eigen rack, apparatuur en soms getimede gebeurtenissen.
2. Klik **Toewijzen & starten**.
3. De simulator **wist** de huidige wereld van die student en **bouwt** hem opnieuw op
   volgens het scenario (rack + apparaten). Eventuele getimede acties worden ingepland.

Zo geef je elke student (of elk koppel) een **andere** startsituatie. De scenario's dekken
o.a. predictief onderhoud, storingen, netwerk-/security-incidenten, cascade-uitval en een
leeg rack om in te richten.

### 6.3 Studenten aan elkaar koppelen (tegenrollen)

Geef elk koppel zijn tegenrollen, zodat de twee studenten elkaars leidinggevende/klant zijn:

1. Kies bij een student in de kolom **Partner (tegenrol)** de naam van de partner.
2. Klik **Koppelen**.
3. De koppeling is **wederzijds**: de partner wordt automatisch terug gekoppeld. Een eerdere
   koppeling van beide studenten wordt eerst losgemaakt. Kies **— geen partner —** om te
   ontkoppelen.

De huidige partner staat per student vermeld (achter "Partner:"). Wat een gekoppelde student
in de partnerwereld mag, regelt de simulator automatisch: **goedkeuren** en **melding maken**,
maar geen technische handelingen (zie §5 en §9).

### 6.4 De wereld van een student resetten

- Klik bij de student op **Reset**. De wereld wordt leeggemaakt en — als er een scenario is
  toegewezen — opnieuw opgebouwd vanuit dat scenario.

Gebruik dit om een student opnieuw te laten beginnen, of om na een mislukte oefening met een
schone lei verder te gaan, zónder de hele database te resetten.

### 6.5 Volledige reset (fabrieksinstelling) — vanuit de app

Voor een nieuwe klas of een complete schoonmaak hoef je in de cloud **geen terminal** te
gebruiken. Onderaan **Studenten beheren** staat de rode knop **Volledige reset…**:

1. Klik **Volledige reset…**.
2. Typ in het venster het woord **`RESET`** (in hoofdletters) ter bevestiging.
3. Klik **Definitief resetten**.

Dit wist **alle** data — alle studentaccounts, koppels, werelden én bewijs — en zet de
demo-omgeving (rack R03, MediCloud, voorbeeldtickets, scenario's) plus de zes standaard­
accounts terug. Daarna word je **uitgelogd**; log opnieuw in als `docent@datacenter-sim.test`
met wachtwoord `password`.

> ⚠️ **Onomkeerbaar en voor iedereen.** De reset verwijdert alle data definitief en logt
> álle gebruikers uit (ook studenten die op dat moment werken). Gebruik dit dus **tussen**
> lessen/klassen, nooit midden in een sessie. Wil je maar één student opnieuw laten beginnen,
> gebruik dan **Reset** per student (§6.4).

> 💡 Dezelfde actie bestaat ook als commando voor wie wél een terminal heeft:
> `php artisan simulate:reset`. Dit werkt ook in productie (anders dan `migrate:fresh`).

### 6.6 Demodata verwijderen of importeren

Onderaan **Studenten beheren** staat de amberkleurige kaart **Demodata** met twee knoppen.
Bij beide blijven **accounts, koppels en de scenario-bibliotheek behouden**.

**Demodata verwijderen…** — voor een schone klas:

1. Klik **Demodata verwijderen…** → **Ja, verwijderen**.
2. Alle simulatie-/wereldgegevens van álle studenten (racks, devices, tickets, alarmen,
   bezoekers, inspecties, berichten, plannen — inclusief de MediCloud-demo) worden verwijderd.
3. Daarna hebben alle studenten een lege wereld; wijs ze een startscenario toe (§6.2).

**Demodata importeren…** — om mee te testen:

1. Klik **Demodata importeren…** → **Ja, importeren**.
2. De MediCloud-demo (rack R03, apparatuur, voorbeeldtickets, plan, berichten, inspectie,
   bezoekers) wordt opnieuw klaargezet in de demo-omgeving.
3. **Andere studenten blijven ongemoeid**, en het is veilig om te herhalen (geen dubbele data).

Verschil met de **volledige reset** (§6.5): die wist óók accounts en zet de demo terug; deze
twee knoppen raken de accounts niet.

> 💡 Als commando's: `php artisan simulate:clear` (verwijderen) en
> `php artisan simulate:import-demo` (importeren).

---

## 7. Scenario's: storingen en startopstellingen

**Menu → Scenario's beheren.**

Er zijn twee soorten scenario's. Het verschil is belangrijk:

| Soort | Wat doet het | Waar gebruik je het |
|---|---|---|
| **Startopstelling (provisioning)** | Bouwt een compleet rack + apparaten op in de wereld van een student | **Studenten beheren** → Toewijzen & starten |
| **Gebeurtenissen (getimede acties)** | Zet bestaande apparaten op een vertraging naar een nieuwe status | **Scenario's beheren** → Start, in de **actieve wereld** |

De seed bevat van elk een voorbeeld: *"Startopstelling MediCloud (rack R03)"* (provisioning)
en *"NOC-oefening: koeling valt geleidelijk uit"* (getimede acties).

### 7.1 Direct een storing triggeren

Bovenaan **Scenario's beheren** staat **Directe status**:

1. Kies een **apparaat** (bv. *medicloud-app01*).
2. Kies de **status**: *Actief*, *Waarschuwing*, *Storing* of *Offline*.
3. Klik **Direct toepassen**.

De wijziging gaat meteen in en wordt realtime naar het monitoring-dashboard van die wereld
gestuurd. Handig om in één klik een incident te creëren waar de student op moet reageren.

> Let op: dit werkt op de apparaten in de **actieve wereld**. Kies dus eerst de juiste
> student als **Actieve student** (zie [§9](#9-acteren-als-gedeelde-rol-leidinggevendeklant)).

### 7.2 Een scenario met gebeurtenissen samenstellen

In de **scenario-bouwer**:

1. Geef het scenario een **naam** en **beschrijving**.
2. Voeg met **Actie toevoegen** stappen toe. Elke actie heeft:
   - een **vertraging** in seconden (vanaf de start),
   - een **apparaat**,
   - de **status** waar dat apparaat naartoe gaat.
3. Klik **Scenario opslaan**.

Voorbeeld: na 30 s → *app02* op **Waarschuwing**, na 120 s → *db01* op **Storing**. Zo loopt
een storing geleidelijk op en kan de student in de waarschuwingsfase ingrijpen (opdracht 4).

### 7.3 Een scenario starten

- Klik bij een opgeslagen scenario op **Start**. De acties worden als geplande taken in de
  wachtrij gezet en op hun tijdstip uitgevoerd. Elke statuswijziging verschijnt realtime op
  het dashboard en genereert waar nodig een alarm.

> 💡 Hiervoor moet de **queue-worker** draaien (`php artisan queue:listen`, of via
> `composer run dev`). Zonder worker worden de vertraagde acties niet uitgevoerd.

---

## 8. Monitoring & "Simuleer tick"

**Menu → Monitoring (NOC).**

- Per apparaat zie je **status**, **CPU** en **temperatuur**, plus een **alarmpaneel**.
- Drempels: **Waarschuwing** vanaf CPU ≥ 85% of temp ≥ 65 °C; **Storing** vanaf CPU ≥ 95%
  of temp ≥ 80 °C.
- Met de knop **Simuleer tick** spoel je de tijd één stap vooruit: apparaten met een
  oplopende trend (predictief, bv. *medicloud-app01*) bewegen richting waarschuwing en
  daarna storing. Zo oefen je het **vroege signaal** voor predictive maintenance.
- Het dashboard ververst automatisch en reageert realtime op statuswijzigingen in **die**
  wereld.

De tick werkt op de **actieve wereld**: een technicus tickt zijn eigen wereld; jij als docent
tickt de wereld die je als **Actieve student** hebt gekozen. De automatische tick (elke
minuut, als `schedule:work` draait) loopt over **alle** werelden tegelijk.

---

## 9. Acteren als gedeelde rol (leidinggevende/klant) en koppels

Voor compleet examenbewijs moeten sommige handelingen door een **leidinggevende** (plan/ticket
goedkeuren) of **klant** (melding maken) gebeuren — en wel **in de wereld van de betreffende
student**. Er zijn twee manieren waarop dat gebeurt:

- **Via een koppel (aanrader, zie §6.3):** de partner van de student handelt als
  leidinggevende/klant in diens wereld. De partner kiest daarvoor bovenin **Actieve student →
  Partner: «naam»**.
- **Via een gedeeld instructeur-account** (`leidinggevende@…` / `klant@…`, of jij als docent):
  kies bovenin **Actieve student** de juiste student.

**Zo werk je in de wereld van een student:**

1. Kijk in de zijbalk naar het keuzemenu **Actieve student** en kies de student (of, als
   partner, *Partner: «naam»*).
2. Bovenaan verschijnt een **groene balk**: *"Je werkt in de omgeving van: «naam»"*. Vanaf nu
   landen jouw handelingen in díe wereld en zie je alleen díe data.
3. Doe de actie (leidinggevende → plan/ticket **Goedkeuren**; klant → **Melding maken**).
4. Klaar? Zet **Actieve student** terug op een andere student of op overzicht.

> ✅ **De simulator dwingt dit nu af.** Heb je géén actieve student gekozen, dan zie je een
> **oranje balk** *"Overzichtsmodus"* en zijn schrijfacties **geblokkeerd**: probeer je dan
> toch iets aan te maken of goed te keuren, dan krijg je de melding *"Kies eerst een actieve
> student"* en gebeurt er niets. Zo kan bewijs niet meer in een gedeelde laag belanden of bij
> de verkeerde student terechtkomen. Kijken/overzicht mag wel zonder keuze.

> 💡 Een **technicus** (student) ziet in eigen wereld alleen zijn eigen rol; pas wanneer hij
> via de switcher naar de **partnerwereld** schakelt, krijgt hij daar de leidinggevende/klant-
> rechten — nooit het technische uitvoeren. Daardoor kan een student zijn eigen werk niet
> aftekenen (vier-ogen blijft gewaarborgd).

---

## 10. Toezicht houden en beoordelen

Je hebt inzage in alle modules. Wil je het werk van één student beoordelen, kies die student
dan eerst als **Actieve student** en loop daarna langs:

- **Tickets** — afhandeling van incidenten/wijzigingen: diagnose, actie, vier-ogen-controle
  (controleur ≠ uitvoerder), goedkeuring leidinggevende en de **SLA-badge**
  (P1 = 60 min, P2 = 240 min, P3 = 480 min; "Binnen SLA" / "Buiten SLA").
- **Racks (DCIM)** — de fysieke indeling en de automatisch gelogde wijzigingen (wie, wanneer).
- **Monitoring** — actuele toestand, CPU/temperatuur en het alarmlog.
- **Inspectierondes** — de ingevulde inspectierapporten (5 controlepunten).
- **Toegangsregister** — bezoekers met aan- én afmelding.
- **Berichten** — de communicatie met collega/melder.
- **Portfoliobewijs** — per opdracht (1 t/m 6) of het bewijs **compleet** is, met een
  **PDF-export** als eindbewijs. Onvolledig bewijs toont wat er nog ontbreekt.

> De simulator logt bij elke wijziging automatisch **datum + naam**. Handtekeningen op
> screenshots zijn niet meer nodig: de leidinggevende "tekent af" door in de wereld van de
> student te **Goedkeuren**.

---

## 11. Een complete oefenronde opzetten

Een voorbeeld van een volledige cyclus:

1. **Voorbereiden:** `php artisan migrate:fresh --seed`, daarna `composer run dev` (en voor
   realtime `php artisan reverb:start`).
2. **Studenten klaarzetten:** maak per student een account aan (**Studenten beheren**) en
   wijs ieder een **Startscenario** toe (Toewijzen & starten).
3. **Situatie creëren:** trigger een storing (**Directe status**) of start een scenario met
   gebeurtenissen in de wereld van de student.
4. **Klantmelding:** kies de student als **Actieve student**, log/handel als **klant** en
   maak een melding (**Melding maken**).
5. **Student lost op:** de student (technicus) pakt het ticket op, stelt diagnose en actie
   vast, herstelt het apparaat in DCIM, wijst een **controleur** toe.
6. **Aftekenen:** handel als **leidinggevende** (Actieve student = de student) en **keur**
   het plan/ticket **goed**; de student sluit het ticket.
7. **Beoordelen:** controleer in **Portfoliobewijs** of de opdracht compleet is en exporteer
   de PDF.

---

## 12. Realtime updates (Reverb) voor de demo

Voor live bijwerkende dashboards (zonder verversen) gebruikt de simulator **Laravel Reverb**
(websockets). Start naast `composer run dev` ook:

```bash
php artisan reverb:start
```

Zonder Reverb werkt alles nog steeds — het Monitoring-dashboard ververst dan periodiek
vanzelf in plaats van direct. Voor scenario's met vertraging moet sowieso de **queue-worker**
draaien (zit in `composer run dev`).

---

## 13. Veelvoorkomende handelingen (cheatsheet)

| Handeling | Menu / commando |
|---|---|
| Nieuwe klas opzetten (terminal) | `php artisan migrate:fresh --seed` |
| Volledige reset vanuit de app | Studenten beheren → Volledige reset… → typ `RESET` |
| Volledige reset (terminal, ook in cloud) | `php artisan simulate:reset` |
| Alleen demodata verwijderen | Studenten beheren → Demodata verwijderen… (of `php artisan simulate:clear`) |
| Demodata (opnieuw) importeren | Studenten beheren → Demodata importeren… (of `php artisan simulate:import-demo`) |
| App + queue + assets starten | `composer run dev` |
| Realtime aanzetten | `php artisan reverb:start` |
| Automatische tick aanzetten | `php artisan schedule:work` |
| Eén tick handmatig (alle werelden) | `php artisan simulate:tick` |
| Eén tick voor één student | `php artisan simulate:tick --student=<id>` |
| Student toevoegen | Studenten beheren → Toevoegen |
| Startscenario toewijzen | Studenten beheren → Toewijzen & starten |
| Wereld resetten | Studenten beheren → Reset |
| Direct laten storen | Scenario's beheren → Directe status → Direct toepassen |
| Scenario met vertraging | Scenario's beheren → bouwen → Opslaan → Start |
| In de wereld van een student werken | Zijbalk → Actieve student → kies naam |

---

## 14. Problemen oplossen

| Symptoom | Oorzaak / oplossing |
|---|---|
| *"no such table"* of lege pagina's | Database niet (goed) opgezet → `php artisan migrate:fresh --seed`. |
| Frontend-fout *"Unable to locate file in Vite manifest"* | Assets niet gebouwd → `npm run dev` (of `npm run build`). |
| Scenario met vertraging gebeurt niet | Queue-worker draait niet → `php artisan queue:listen` (of `composer run dev`). |
| Metrics veranderen niet vanzelf | Scheduler draait niet → `php artisan schedule:work`, of klik handmatig **Simuleer tick**. |
| Dashboard werkt niet realtime | Reverb draait niet → `php artisan reverb:start`. Zonder Reverb ververst het dashboard periodiek. |
| Nieuwe student ziet geen apparatuur | Dat klopt: nieuwe werelden zijn leeg. Wijs een **Startscenario** toe. |
| Ik zie data van meerdere studenten door elkaar | Je staat op **"Alle studenten (overzicht)"**. Kies een specifieke **Actieve student**. |
| Ticket kan niet worden afgesloten | Vier-ogen: er moet een **controleur** (≠ uitvoerder) zijn én een goedkeuring van de leidinggevende. |

---

## 15. Aandachtspunten en beperkingen

- **Overzichtsmodus is "alleen kijken" — en dat wordt afgedwongen.** Als gedeelde rol zonder
  gekozen **Actieve student** zie je alle werelden (oranje balk), maar schrijfacties zijn
  geblokkeerd met de melding *"Kies eerst een actieve student"*. Kies dus eerst de student
  (of, als partner, de partnerwereld) voordat je iets aanmaakt of aftekent.
- **Een volledige reset wist alles en logt iedereen uit.** Zowel `migrate:fresh --seed`
  (terminal) als **Volledige reset…** (knop, §6.4) verwijdert álle data definitief. Gebruik
  het tussen lessen, niet midden in een sessie. Wil je maar één student opnieuw laten
  beginnen, gebruik dan **Reset** per student (§6.3).
- **Fysieke bekabeling/patching** is geabstraheerd: de student beschrijft dit in het
  installatieplan en plaatst apparaten op U-hoogte in het rack; de simulator visualiseert het
  rack, niet de losse kabels.
- **Wachtwoorden** zijn voor alle (ook nieuw aangemaakte) accounts `password`. Voor een echte
  productie-inzet zou je dit willen aanscherpen; voor de lesomgeving is het bewust simpel.

---

Zie ook de rolhandleidingen voor [technicus](technicus.md), [leidinggevende](leidinggevende.md)
en [klant](klant.md), en het [overzicht](README.md).
