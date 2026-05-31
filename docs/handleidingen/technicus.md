# Handleiding — Technicus

> **Wie ben jij?** Je bent de student-technicus. Jij voert vrijwel alle praktijkopdrachten
> uit: je installeert apparatuur, lost storingen op, handelt tickets af, bewaakt de
> infrastructuur, inspecteert en begeleidt bezoekers. Elke handeling die je doet, levert
> bewijs op voor je portfolio.

**Inloggen:** `technicus@datacenter-sim.test` / `password`

## Wat zie je in het menu?

In de zijbalk vind je: Dashboard, Racks (DCIM), Tickets, Monitoring, Toegangsregister,
Inspectierondes, Berichten en Portfoliobewijs. Als technicus zie je daarnaast het
menu-item **Opdrachten uitvoeren**.

---

## Jouw eigen omgeving en je partner

- Je werkt in een **eigen, afgeschermde omgeving**. Wat jij doet, ziet een medestudent niet —
  en jij ziet hun werk niet. Je begint mogelijk met een **lege** omgeving; de docent kan je
  een startscenario toewijzen waarmee een rack klaarstaat, óf je **bouwt zelf een rack** op
  (zie hieronder bij Racks).
- Je bent **technicus in je eigen wereld**: je voert de opdrachten uit (DCIM, tickets,
  inspectie, toegangsregister, monitoring).
- **Tegenrol bij je partner.** Heeft de docent jou aan een partner gekoppeld, dan zie je
  bovenin de zijbalk een keuze **Actieve student** met *Mijn omgeving* en *Partner: «naam»*.
  - In **Mijn omgeving** doe jij het technische werk.
  - Schakel je naar **Partner: «naam»**, dan handel je in de wereld van je partner als
    **leidinggevende/klant**: je mag daar plannen en tickets **goedkeuren** en **meldingen
    maken**, maar géén technische handelingen doen. Bovenaan zie je dan een groene balk
    *"Je werkt in de omgeving van: «naam»"*.
- Zo tekenen koppels elkaars werk af. Je kunt je **eigen** werk niet goedkeuren — dat is de
  vier-ogen-eis, en de simulator bewaakt dit.

> 💡 Vergeet na het helpen van je partner niet terug te schakelen naar **Mijn omgeving**,
> anders landt je volgende handeling in de verkeerde wereld.

---

## 1. Racks bekijken en apparatuur beheren (DCIM)

**Menu → Racks**

- Je ziet een visueel rackoverzicht (rack **R03** in **DC-Utrecht**, 42U). Elk apparaat is
  een gekleurd blok op zijn U-positie:
  - 🟢 **Actief** — alles normaal
  - 🟠 **Waarschuwing** — vroeg signaal (CPU ≥ 85% of temperatuur ≥ 65 °C)
  - 🔴 **Storing** — kritiek (CPU ≥ 95% of temperatuur ≥ 80 °C)
  - ⚪ **Offline**
- **Rack toevoegen:** klik rechtsboven op _Rack toevoegen_ en vul **naam** (bv. R04),
  **locatie** (bv. DC-Utrecht) en **hoogte in U** (bv. 42) in. Het nieuwe rack verschijnt
  meteen in je eigen omgeving, klaar om te vullen.
- **Apparaat toevoegen:** klik op _Device toevoegen_, vul naam, type (server/switch/
  router/storage/firewall), U-positie (start–eind) en metrics in.
- **Apparaat wijzigen:** klik een bestaand blok aan en pas het aan, of verplaats het naar een
  ander rack.
- **Rack verwijderen:** klik op het prullenbak-icoon bij een rack. Let op: dit verwijdert het
  rack **inclusief alle apparaten erin** (je krijgt eerst een bevestiging).

> 📸 **Bewijs (opdracht 1, 5, 6):** Elke wijziging logt automatisch wie het deed en
> wanneer (`last_changed_at` / `last_changed_by`). Maak een screenshot van het rackoverzicht.

---

## 2. Tickets afhandelen (opdracht 4 en 5)

**Menu → Tickets**

In de lijst zie je alle tickets met type (INC/CHG/SR), prioriteit en een **SLA-badge**
(Binnen SLA / Buiten SLA). Filter op status of prioriteit.

Open een ticket om het af te handelen. De werkstroom verloopt via statussen:

1. **Open** → **In behandeling** → **Wachten op controle** → **Afgesloten**
2. Gebruik de knop **Volgende status** om door de werkstroom te lopen.
3. Vul **diagnose** en **actie** in — dit is je bewijs dat je de storing hebt gelokaliseerd
   en verholpen.

### Vier-ogen-principe (belangrijk!)

Een ticket kan **alleen worden afgesloten** als een *andere* gebruiker dan jij als
**controleur** is aangewezen. Jij voert uit (`assigned_to`), iemand anders controleert
(`checked_by`).

- Wijs via **Controleur toewijzen** een andere gebruiker aan (niet jezelf) — bijvoorbeeld je
  **partner** of de leidinggevende.
- Daarna keurt de leidinggevende (of je partner in de leidinggevende-rol, via *Actieve
  student → jouw naam*) het ticket goed.
- Pas dan werkt de knop **Ticket afsluiten**.

> 📸 **Bewijs (opdracht 4):** ticket met diagnose, actie, prioriteit en SLA-status.
> 📸 **Bewijs (opdracht 5):** ticket + de berichten die je naar de melder stuurde.

### SLA-bewaking

De prioriteit bepaalt de doorlooptijd: **P1 = 60 min**, **P2 = 240 min**, **P3 = 480 min**.
Bij elk ticket zie je of het binnen of buiten de SLA is afgehandeld (`closed_at` versus
aanmaaktijd + SLA-minuten).

---

## 3. Monitoring / NOC (opdracht 2)

**Menu → Monitoring**

- Bovenaan een statusoverzicht (aantal apparaten per status).
- Per apparaat een kaart met CPU- en temperatuurbalken.
- Een **alarmpaneel** met de laatste meldingen (`DeviceAlerts`).
- Het dashboard ververst automatisch (elke 10 seconden) en reageert realtime op
  statuswijzigingen.
- Met de knop **Tick uitvoeren** simuleer je het verstrijken van tijd; apparaten met een
  oplopende metric-trend (zoals **medicloud-app01**) bewegen dan richting een waarschuwing.
  Zo kun je in de **waarschuwingsfase** ingrijpen vóór een echte storing (predictief).

> 📸 **Bewijs (opdracht 2):** screenshot van het dashboard + het incident-/alarmlog.

---

## 4. Inspectieronde uitvoeren (opdracht 3)

**Menu → Inspectierondes**

- Het formulier heeft vijf vaste controlepunten: **Koeling**, **Stroom (UPS/PDU)**,
  **Brandveiligheid**, **Security/toegang**, **Racks/kabelmanagement**.
- Geef per punt een status: **Ok**, **Afwijking** of **Actie**, eventueel gekoppeld aan een
  apparaat, met een waarneming.
- Klik **Opslaan** om het rapport vast te leggen.
- Een **Afwijking** zet het gekoppelde apparaat op _Waarschuwing_; een **Actie** zet het op
  _Storing_ — zo werkt je inspectie door in de monitoring.

> 📸 **Bewijs (opdracht 3):** het ingevulde inspectierapport.

---

## 5. Bezoek begeleiden (opdracht 6 — cruciaal)

**Menu → Toegangsregister**

- **Aanmelden:** vul bezoekersnaam, bedrijf, reden en badgenummer in en klik
  **Registreren**. Het tijdstip van binnenkomst (`checked_in_at`) wordt vastgelegd.
- **Afmelden:** klik bij de bezoeker op **Afmelden** wanneer hij vertrekt
  (`checked_out_at`).

> ⚠️ **Let op:** voor geldig bewijs moeten **beide** tijdstempels gevuld zijn — aanmelden
> én afmelden. Een bezoeker met alleen een check-in telt nog niet als compleet bewijs.

> 📸 **Bewijs (opdracht 6):** het toegangsregister met zowel aan- als afmeldtijd.

---

## 6. Communiceren (opdracht 3 en 5)

**Menu → Berichten**

- Kies een ontvanger (collega of melder), eventueel gekoppeld aan een ticket, en typ je
  bericht.
- Op een ticketpagina kun je ook direct in de berichtenstroom van dat ticket reageren.

> 📸 **Bewijs (opdracht 3 en 5):** de berichten naar je collega of de melder.

---

## 7. Bewijs exporteren

**Menu → Portfoliobewijs**

- Per opdracht (1 t/m 6) zie je een kaart met de status: **compleet** of **onvolledig**.
- Voor opdracht 1, 4 en 5 selecteer je eerst het bijbehorende ticket.
- Is een opdracht compleet, dan download je het bewijs als **PDF**.
- Is het onvolledig, dan zie je wat er nog ontbreekt.

> 💡 Werk de opdrachten af in de aanbevolen volgorde en controleer per opdracht of het
> bewijs compleet is voordat je de PDF exporteert.
