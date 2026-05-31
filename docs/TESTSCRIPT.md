# Testscript — Datacenter Simulator (samen met een student)

Een doorloop van ±30 minuten om te controleren of de hele examen-werkstroom werkt:
isolatie per student, een incident afhandelen met vier-ogen, bewijs exporteren, en
het beheer door de docent. Vink elke stap af; bij **Verwacht** staat wat je moet zien.

> **Rollen tijdens deze test:** jij (docent) speelt ook even de *klant* en de
> *leidinggevende* in de wereld van de student, via de keuze **Actieve student**
> bovenin de zijbalk. Heb je twee studenten als koppel, doe dan deel 7 (koppels).

**App:** ____________________  (bv. http://localhost:8000)
**Datum / student:** ____________________

Demo-accounts (wachtwoord `password`, tenzij gewijzigd):
`docent@datacenter-sim.test` · `leidinggevende@datacenter-sim.test` ·
`klant@medicloud.test` · de student gebruikt zijn eigen technicus-account.

---

## Deel 1 — Voorbereiding (docent)

| # | Wie | Doe | Verwacht | ✓ |
|---|-----|-----|----------|---|
| 1.1 | Docent | Log in als `docent@…` | Je ziet het **Klasoverzicht** op het dashboard | ☐ |
| 1.2 | Docent | **Studenten beheren** → maak een student aan (naam + e-mail), of gebruik *Sanne Student* | De student verschijnt in de lijst | ☐ |
| 1.3 | Docent | Bij die student: kies **Startscenario** = *"Startopstelling MediCloud (rack R03)"* → **Toewijzen & starten** | Melding "Scenario toegewezen en wereld opgebouwd"; bij de student staat nu een aantal devices | ☐ |

---

## Deel 2 — Student start in een eigen, lege/gevulde wereld

| # | Wie | Doe | Verwacht | ✓ |
|---|-----|-----|----------|---|
| 2.1 | Student | Log in op zijn eigen account (ander venster/incognito) | Dashboard met **Mijn examenvoortgang** (x/6) | ☐ |
| 2.2 | Student | Open **Racks (DCIM)** | Eigen rack met de toegewezen apparatuur | ☐ |
| 2.3 | Student | Open **Monitoring** | Statussen + CPU/temp van de eigen apparaten | ☐ |

---

## Deel 3 — Isolatie controleren (de kern)

| # | Wie | Doe | Verwacht | ✓ |
|---|-----|-----|----------|---|
| 3.1 | Docent | Wijs (Studenten beheren) een **tweede** student ook een scenario toe | Tweede student heeft eigen devices | ☐ |
| 3.2 | Student | Ververs **Racks (DCIM)** | Student ziet **alleen zijn eigen** apparatuur, niet die van de ander | ☐ |
| 3.3 | Student | Voeg een apparaat toe of wijzig er een | Verschijnt alleen in zijn eigen wereld | ☐ |

---

## Deel 4 — Een incident volledig afhandelen (opdracht 4/5 + vier-ogen)

> Let op de werkstroom: **afsluiten** kan zodra er een **controleur** is die niet de
> uitvoerder is (vier-ogen). De **goedkeuring door de leidinggevende** is een aparte
> aftekening voor het portfolio en is geen voorwaarde om te kunnen afsluiten.

| # | Wie | Doe | Verwacht | ✓ |
|---|-----|-----|----------|---|
| 4.1 | Docent (als klant) | Log in als `klant@…`, kies **Actieve student** = de student | **Groene balk** "Je werkt in de omgeving van: «naam»" | ☐ |
| 4.2 | Docent (als klant) | **Melding maken**: incident, prioriteit P1, kies een apparaat | Ticket aangemaakt (nummer `INC-…`) in de wereld van de student | ☐ |
| 4.3 | Student | Open **Tickets** → het nieuwe ticket | Ziet het ticket met **SLA-badge** | ☐ |
| 4.4 | Student | **Volgende status** tot *Wachten op controle*; vul diagnose/actie in | Status loopt op | ☐ |
| 4.5 | Student | Probeer **Ticket afsluiten** zónder controleur | Geblokkeerd: melding "vier-ogen-controle ontbreekt" | ☐ |
| 4.6 | Student | **Controleur toewijzen** = een andere gebruiker (niet jezelf) → **Ticket afsluiten** | Status *Afgesloten*; SLA-badge toont binnen/buiten SLA | ☐ |
| 4.7 | Docent (als leidinggevende) | Log in als `leidinggevende@…`, **Actieve student** = de student, open het ticket → **Goedkeuren** | Ticket afgetekend (naam + tijd) | ☐ |
| 4.8 | Student/docent | Stuur via **Berichten** een terugkoppeling aan de melder | Bericht verschijnt in de tijdlijn | ☐ |

---

## Deel 5 — De beveiliging (guard + banner) controleren

| # | Wie | Doe | Verwacht | ✓ |
|---|-----|-----|----------|---|
| 5.1 | Docent (als leidinggevende) | Zet **Actieve student** op *"Alle studenten (overzicht)"* | **Oranje balk** "Overzichtsmodus" | ☐ |
| 5.2 | Docent (als leidinggevende) | Probeer in die stand een ticket **Goed te keuren** | Geblokkeerd met melding *"Kies eerst een actieve student"* | ☐ |
| 5.3 | Docent | Kies weer een student → keuren lukt wel | Actie slaagt | ☐ |

---

## Deel 6 — Overige opdrachten kort (2, 3, 6) + bewijs

| # | Wie | Doe | Verwacht | ✓ |
|---|-----|-----|----------|---|
| 6.1 | Student | **Monitoring** → **Simuleer tick** een paar keer | Een apparaat loopt op naar *Waarschuwing*/*Storing* (opdracht 2/4) | ☐ |
| 6.2 | Student | **Inspectierondes** → vul de 5 punten in, zet er 1 op *Afwijking* met een gekoppeld apparaat → **Opslaan** | Rapport opgeslagen; het gekoppelde apparaat gaat automatisch naar *Waarschuwing* (opdracht 3) | ☐ |
| 6.3 | Student | **Toegangsregister** → **Registreren** (bezoeker) en daarna **Afmelden** | Bezoeker met aan- én afmeldtijd (opdracht 6) | ☐ |
| 6.4 | Student | **Portfoliobewijs** → kies een afgeronde opdracht → **PDF** | PDF downloadt met datum + naam; onvolledige opdrachten tonen wat ontbreekt | ☐ |

---

## Deel 7 (optioneel) — Koppels / tegenrollen (met twee studenten)

| # | Wie | Doe | Verwacht | ✓ |
|---|-----|-----|----------|---|
| 7.1 | Docent | **Studenten beheren** → koppel student A en B (**Partner** → **Koppelen**) | Beide tonen elkaar als partner | ☐ |
| 7.2 | Student A | Zijbalk **Actieve student** → *Partner: B* | Groene balk "omgeving van B"; A mag in B's wereld **goedkeuren/melden**, maar geen technisch werk | ☐ |
| 7.3 | Student A | Keur een ticket/plan van B af/goed | Lukt; A kan zijn **eigen** werk niet aftekenen (vier-ogen) | ☐ |

---

## Deel 8 — Afronden / resetten (docent)

| # | Wie | Doe | Verwacht | ✓ |
|---|-----|-----|----------|---|
| 8.1 | Docent | **Studenten beheren** → bij een student **Reset** | Wereld leeg en opnieuw opgebouwd uit het scenario | ☐ |
| 8.2 | Docent | (alleen als je echt alles wilt wissen) **Volledige reset…** → typ `RESET` | Alles terug naar fabrieksinstelling; je wordt uitgelogd | ☐ |

---

## Uitkomst

- Alles afgevinkt? Dan werkt de volledige examen-werkstroom (isolatie, vier-ogen,
  SLA, bewijs-export, beheer) zoals bedoeld.
- Genoteerde problemen:

  ____________________________________________________________________

  ____________________________________________________________________

**Getest door:** ____________________  **Akkoord:** ☐
