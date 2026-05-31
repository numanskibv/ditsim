# Handleidingen — Datacenter Simulatie

Deze map bevat een handleiding per gebruikersrol. De applicatie simuleert een
datacenter-werkomgeving voor het keuzedeel **Datacenter IT techniek (K1171)**. Elk
scherm levert verifieerbaar bewijs op (screenshot/PDF) voor je examenportfolio.

## Inloggen (demo-accounts)

Na het seeden van de database (`php artisan migrate:fresh --seed`) zijn deze accounts
beschikbaar. Het wachtwoord is voor alle accounts **`password`**.

| Rol | E-mail | Naam | Bijzonderheid |
|-----|--------|------|---------------|
| Technicus (student) | `technicus@datacenter-sim.test` | Tessa Technicus | heeft de gevulde demo-wereld |
| Technicus (student) | `sanne@datacenter-sim.test` | Sanne Student | start leeg · gekoppeld aan Sven |
| Technicus (student) | `sven@datacenter-sim.test` | Sven Student | start leeg · gekoppeld aan Sanne |
| Leidinggevende | `leidinggevende@datacenter-sim.test` | Laura Leidinggevende | |
| Klant | `klant@medicloud.test` | MediCloud BV | |
| Docent | `docent@datacenter-sim.test` | Dirk Docent | beheert studenten & scenario's |

## Werelden en koppels (belangrijk)

Elke student-technicus werkt in een **eigen, afgeschermde omgeving** — je ziet elkaars
apparatuur, tickets en bewijs niet. De rollen leidinggevende/klant/docent (en de **partner**
binnen een koppel) handelen in de wereld van een student via de keuze **Actieve student**
bovenin de zijbalk. Zonder die keuze sta je in **Overzichtsmodus** en zijn schrijfacties
geblokkeerd. Lees per rol hoe dit werkt.

## Handleiding per rol

- [Technicus](technicus.md) — voert de praktijkopdrachten uit (de student)
- [Leidinggevende](leidinggevende.md) — keurt plannen en tickets goed, tekent af
- [Klant](klant.md) — maakt meldingen aan en bezoekt het datacenter
- [Docent](docent.md) — beheert scenario's en triggert storingen (admin)

## Welke module dekt welke opdracht?

| Opdracht | Werkproces | Belangrijkste bewijs |
|----------|-----------|----------------------|
| 1 | Installatie volgens goedgekeurd plan | Goedgekeurd installatieplan (PDF) |
| 2 | Monitoren en bewaken (NOC) | Monitoring-dashboard + incidentlog |
| 3 | Onderhoud en inspectie | Ingevuld inspectierapport |
| 4 | Storing lokaliseren en verhelpen | Incident-ticket met diagnose en actie |
| 5 | Incident afhandelen en terugkoppelen | Ticket + communicatielog |
| 6 | Bezoek begeleiden / fysieke toegang | Toegangsregister met aan- én afmelden |

Het bewijs verzamel je per opdracht via het scherm **Portfoliobewijs** en exporteer je
als PDF.

## Voor de beheerder/docent die de app host

Wil je de simulator zelf draaien of overdragen aan een andere docent? Zie de
[installatie- & overdrachtsgids](../DEPLOYMENT.md) (lokaal, Docker Compose, of cloud).
