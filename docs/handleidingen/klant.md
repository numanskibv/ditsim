# Handleiding — Klant

> **Wie ben jij?** Je bent de klant (in de demo: **MediCloud BV**). Jouw apparatuur staat in
> het datacenter. Je maakt meldingen aan wanneer er iets mis is of wanneer je een wijziging
> of dienst nodig hebt, en je houdt via berichten contact met de technicus. Je voert zelf
> geen technische handelingen uit.

**Inloggen:** `klant@medicloud.test` / `password`

## Wat zie je in het menu?

Naast de gedeelde overzichten heb jij het menu-item **Melding maken**.

---

## Eerst: kies de juiste student-omgeving

Elke student werkt in een **eigen, afgeschermde omgeving**. Een melding hoort thuis in de
wereld van één student. Kies daarom **vóór je een melding maakt** bovenin de zijbalk bij
**Actieve student** de juiste naam.

- Zonder keuze sta je in **Overzichtsmodus** (oranje balk) en is **een melding maken
  geblokkeerd** (melding *"Kies eerst een actieve student"*). Dit voorkomt dat je melding bij
  de verkeerde student of in een gedeelde laag belandt.
- Met een student gekozen verschijnt een **groene balk** *"Je werkt in de omgeving van:
  «naam»"* en komt je melding correct in díe wereld.

> 💡 In een klas met koppels speelt vaak de **partner** van de student deze klant-rol (die
> kiest dan *Actieve student → de student*). Het losse account `klant@…` blijft daarnaast
> bruikbaar, bijvoorbeeld voor de docent.

---

## 1. Een melding (ticket) maken

**Menu → Melding maken** (of via **Tickets → nieuw**)

Vul het meldingsformulier in:

- **Type:**
  - **Incident** (INC) — er is iets stuk of werkt niet goed.
  - **Wijziging / Change** (CHG) — je vraagt een aanpassing aan de inrichting.
  - **Serviceverzoek / Service Request** (SR) — bijvoorbeeld extra opslag of capaciteit.
- **Titel** — korte omschrijving (bv. _"Database medicloud-db01 reageert traag"_).
- **Omschrijving** — wat merk je, sinds wanneer, welke impact.
- **Prioriteit** — bepaalt de reactietijd (SLA):
  - **P1 (Kritiek)** — 60 minuten
  - **P2 (Hoog)** — 240 minuten
  - **P3 (Normaal)** — 480 minuten
- **Betrokken apparaat** — kies waar mogelijk het juiste device (bv. _medicloud-db01_).

Na opslaan krijgt je melding automatisch een nummer (bv. `INC-2026-0001`) en gaat hij naar
de technicus.

> 💡 Kies de prioriteit eerlijk: P1 is alleen voor echt kritieke verstoringen. De prioriteit
> bepaalt binnen welke tijd de technicus moet handelen.

---

## 2. De status van je melding volgen

**Menu → Tickets**

Je ziet je meldingen met hun status:

- **Open** — ontvangen, nog niet opgepakt.
- **In behandeling** — de technicus werkt eraan.
- **Wachten op controle** — opgelost, wordt nog gecontroleerd (vier-ogen).
- **Afgesloten** — afgehandeld.

Open een ticket om de diagnose, de uitgevoerde actie en de SLA-status te bekijken.

---

## 3. Contact houden via berichten

**Menu → Berichten**

- Stuur de technicus een bericht, eventueel gekoppeld aan je ticket.
- Op de ticketpagina kun je rechtstreeks in de berichtenstroom van dat ticket reageren.
- Hier krijg je ook de terugkoppeling van de technicus wanneer je melding is afgehandeld.

---

## 4. Een bezoek aankondigen

Wil je als klant het datacenter bezoeken? Dat verloopt via het **Toegangsregister**, dat
door de technicus of leidinggevende wordt bijgehouden. Je wordt bij binnenkomst aangemeld
(met badge en reden) en bij vertrek afgemeld, onder begeleiding van een medewerker. Meld je
bezoek vooraf aan via een **bericht** of een **serviceverzoek**.

---

## Kort samengevat

| Wat | Waar |
|-----|------|
| Storing of verzoek melden | Melding maken |
| Status van je melding volgen | Tickets |
| Contact met de technicus | Berichten |
| Bezoek aankondigen | Bericht / serviceverzoek |
