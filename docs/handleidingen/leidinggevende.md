# Handleiding — Leidinggevende

> **Wie ben jij?** Je bent de leidinggevende. Jij bewaakt de kwaliteit en de veiligheid:
> je keurt installatieplannen goed of af en je tekent tickets af in het kader van het
> vier-ogen-principe. Zonder jouw akkoord kan de technicus bepaalde stappen niet voltooien.

**Inloggen:** `leidinggevende@datacenter-sim.test` / `password`

## Wat zie je in het menu?

Naast de gedeelde schermen (Dashboard, Racks, Tickets, Monitoring, Toegangsregister,
Inspectierondes, Berichten, Portfoliobewijs) heb jij het extra menu-item **Goedkeuringen**.

---

## Eerst: kies de juiste student-omgeving

Elke student werkt in een **eigen, afgeschermde omgeving**. Jij keurt goed *in de wereld van
een specifieke student*. Kies daarom **vóór elke actie** bovenin de zijbalk bij **Actieve
student** de juiste naam.

- Heb je niets gekozen, dan sta je in **Overzichtsmodus** (oranje balk): je ziet alle
  studenten door elkaar en **goedkeuren/afkeuren is geblokkeerd** (melding *"Kies eerst een
  actieve student"*).
- Heb je een student gekozen, dan verschijnt een **groene balk** *"Je werkt in de omgeving
  van: «naam»"* en landen je acties in díe wereld. Op de ticketlijst en het ticket-/plandetail
  zie je bovendien bij welke student het hoort.

> 💡 In een klas met koppels neemt vaak de **partner** van de student deze leidinggevende-rol
> op zich (die kiest dan *Actieve student → de student*). Het losse account
> `leidinggevende@…` blijft daarnaast gewoon bruikbaar, bijvoorbeeld voor de docent.

---

## 1. Installatieplan goedkeuren (opdracht 1)

De technicus stelt voor een wijzigingsticket (type **CHG**) een installatieplan op. Dat
plan moet zes secties bevatten voordat het aan jou wordt voorgelegd:

1. Werkzaamheden
2. Materialenlijst
3. Middelenlijst
4. Betrokken collega
5. Securitymaatregelen — fysiek
6. Securitymaatregelen — virtueel

**Zo keur je een plan:**

1. Open via **Tickets** het betreffende wijzigingsticket en ga naar het installatieplan
   (of open het via **Goedkeuringen**).
2. Lees de zes secties door. Pas als de technicus het plan op _gereed_ heeft gezet, kun je
   beoordelen.
3. Klik **Goedkeuren** — de status wordt **Goedgekeurd** en jouw naam + tijdstip worden
   vastgelegd. Daarna is het plan als **PDF** te downloaden.
4. Klopt iets niet? Klik **Afkeuren** en geef een **reden** op. De status wordt
   **Afgekeurd** en de technicus past het plan aan.

> 📸 **Bewijs (opdracht 1):** het goedgekeurde installatieplan als PDF, met jouw naam en de
> goedkeuringsdatum.

---

## 2. Tickets aftekenen — vier-ogen-controle (opdracht 4 en 5)

Een ticket mag pas naar **Afgesloten** als een *andere* persoon dan de uitvoerend
technicus als controleur is aangewezen, én jij het hebt goedgekeurd. Dit dekt de cruciale
security-criteria.

**Zo tekent je af:**

1. Open een ticket dat de status **Wachten op controle** heeft.
2. Controleer de diagnose en de uitgevoerde actie.
3. Klik **Goedkeuren** (`approve`). Jouw naam en het tijdstip worden vastgelegd
   (`approved_by` / `approved_at`).
4. Pas hierna kan de technicus het ticket afsluiten.

> 💡 Jij voert zelf geen technische handelingen op het ticket uit; jouw rol is beoordelen en
> aftekenen. De statusovergangen en de afsluiting blijven bij de technicus liggen.

---

## 3. Meekijken en bewaken

Je hebt toegang tot alle overzichten om je oordeel te onderbouwen:

- **Racks (DCIM)** — de fysieke indeling en de laatst gewijzigde apparaten.
- **Monitoring** — actuele status, CPU/temperatuur en het alarmlog.
- **Inspectierondes** — de uitgevoerde inspectierapporten.
- **Toegangsregister** — wie wanneer aan- en afgemeld is. Je mag zelf ook als begeleider
  optreden bij een bezoek.
- **Berichten** — communicatie met technici en melders.

---

## 4. Checklist voor goedkeuren

Voordat je goedkeurt, controleer je in elk geval:

- [ ] Zijn alle zes secties van het installatieplan inhoudelijk ingevuld?
- [ ] Zijn de securitymaatregelen (fysiek én virtueel) concreet beschreven?
- [ ] Is bij het ticket een controleur aangewezen die níet de uitvoerder is?
- [ ] Kloppen diagnose en actie met de melding?
- [ ] Is het ticket binnen de SLA afgehandeld (zie de SLA-badge)?

Pas als dit klopt, klik je **Goedkeuren**. Anders **Afkeuren** met een duidelijke reden.
