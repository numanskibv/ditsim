<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Portfoliobewijs opdracht {{ $assignment->value }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1a1a1a; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin: 18px 0 6px; }
        .header { border-bottom: 2px solid #1a1a1a; padding-bottom: 10px; margin-bottom: 16px; }
        .meta { color: #444; font-size: 11px; }
        .meta strong { color: #1a1a1a; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #e5e5e5; font-size: 11px; vertical-align: top; }
        th { background: #f3f4f6; }
        .pre { white-space: pre-wrap; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 10px; color: #fff; }
        .actief { background: #16a34a; } .waarschuwing { background: #ea580c; }
        .storing { background: #dc2626; } .offline { background: #6b7280; }
        .approved { margin-top: 10px; color: #166534; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Portfoliobewijs — Opdracht {{ $assignment->value }}</h1>
        <div style="font-size:13px; font-weight:bold;">{{ $assignment->title() }}</div>
        <div class="meta">
            <strong>Student:</strong> {{ $student->name }}@if ($student->student_number) ({{ $student->student_number }})@endif &nbsp;|&nbsp;
            <strong>Datum:</strong> {{ $date->format('d-m-Y') }} &nbsp;|&nbsp;
            <strong>Opdracht:</strong> {{ $assignment->value }} &nbsp;|&nbsp;
            <strong>Werkproces:</strong> {{ $assignment->werkproces() }}
        </div>
    </div>

    @php
        $renderDcim = function ($racks) {
            return $racks;
        };
    @endphp

    @switch($assignment->value)
        @case(1)
            @php($ticket = $data['ticket']) @php($plan = $data['plan'])
            <h2>Ticket</h2>
            <p>{{ $ticket->number }} — {{ $ticket->title }} ({{ $ticket->status->label() }})</p>

            <h2>Goedgekeurd installatieplan</h2>
            @foreach (\App\Models\InstallationPlan::REQUIRED_FIELDS as $field => $label)
                <p><strong>{{ $label }}:</strong><br><span class="pre">{{ $plan->{$field} ?: '—' }}</span></p>
            @endforeach
            <div class="approved">Goedgekeurd door {{ $plan->approver?->name ?? 'onbekend' }} op {{ optional($plan->approved_at)->format('d-m-Y H:i') }}</div>

            @include('pdf.partials.dcim', ['racks' => $data['racks']])
            @break

        @case(2)
            <h2>Incident-/monitoringlog</h2>
            <table>
                <tr><th>Tijd</th><th>Device</th><th>Melding</th></tr>
                @foreach ($data['alerts'] as $alert)
                    <tr>
                        <td>{{ $alert->created_at->format('d-m-Y H:i:s') }}</td>
                        <td>{{ $alert->device?->name }}</td>
                        <td>{{ $alert->message }}</td>
                    </tr>
                @endforeach
            </table>

            <h2>Tickets</h2>
            <table>
                <tr><th>Nummer</th><th>Titel</th><th>Status</th><th>Prioriteit</th></tr>
                @foreach ($data['tickets'] as $ticket)
                    <tr><td>{{ $ticket->number }}</td><td>{{ $ticket->title }}</td><td>{{ $ticket->status->label() }}</td><td>{{ $ticket->priority->label() }}</td></tr>
                @endforeach
            </table>
            @break

        @case(3)
            @php($inspection = $data['inspection']) @php($message = $data['message'])
            <h2>Inspectierapport — {{ $inspection->date->format('d-m-Y') }}</h2>
            <table>
                <tr><th>Controlepunt</th><th>Beoordeling</th><th>Waarneming</th></tr>
                @foreach ($inspection->items as $item)
                    <tr><td>{{ $item['label'] }}</td><td>{{ \App\Enums\InspectionStatus::from($item['status'])->label() }}</td><td>{{ $item['observation'] ?: '—' }}</td></tr>
                @endforeach
            </table>
            <p style="font-size:11px;">Inspecteur: {{ $inspection->inspector?->name ?? 'onbekend' }}</p>

            <h2>Communicatiebericht</h2>
            <p><strong>{{ $message->sender?->name }} → {{ $message->recipient?->name }}</strong> ({{ $message->sent_at->format('d-m-Y H:i') }})@if ($message->ticket) — ticket {{ $message->ticket->number }}@endif</p>
            <p class="pre">{{ $message->body }}</p>
            @break

        @case(4)
            @php($ticket = $data['ticket'])
            <h2>Reparatieticket — {{ $ticket->number }}</h2>
            <p>{{ $ticket->title }} — {{ $ticket->priority->label() }}</p>
            <table>
                <tr><th>Stap</th><th>Gegeven</th></tr>
                <tr><td>Uitvoerder (actie)</td><td>{{ $ticket->assignee?->name ?? '—' }}</td></tr>
                <tr><td>Controle (vier-ogen)</td><td>{{ $ticket->checker?->name ?? '—' }}</td></tr>
                <tr><td>Resultaat</td><td>{{ $ticket->status->label() }}@if ($ticket->closed_at) — afgesloten op {{ $ticket->closed_at->format('d-m-Y H:i') }}@endif</td></tr>
            </table>

            <h2>Vroeg signaal → storing (metric-historie)</h2>
            @if ($data['alerts']->isNotEmpty())
                <table>
                    <tr><th>Tijd</th><th>Overgang</th><th>cpu/temp</th></tr>
                    @foreach ($data['alerts'] as $alert)
                        <tr><td>{{ $alert->created_at->format('d-m-Y H:i:s') }}</td><td>{{ $alert->message }}</td><td>{{ $alert->cpu }}% / {{ $alert->temp }}°C</td></tr>
                    @endforeach
                </table>
            @else
                <p>Geen gekoppelde device-meldingen.</p>
            @endif
            @break

        @case(5)
            @php($ticket = $data['ticket']) @php($feedback = $data['feedback'])
            <h2>Ticket</h2>
            <p>{{ $ticket->number }} — {{ $ticket->title }} ({{ $ticket->status->label() }})</p>

            <h2>Terugkoppeling naar melder</h2>
            <p><strong>{{ $feedback->sender?->name }} → {{ $feedback->recipient?->name }}</strong> ({{ $feedback->sent_at->format('d-m-Y H:i') }})</p>
            <p class="pre">{{ $feedback->body }}</p>

            @include('pdf.partials.dcim', ['racks' => $data['racks']])
            @break

        @case(6)
            @php($visit = $data['visit'])
            <h2>Begeleidingsverslag</h2>
            <table>
                <tr><th>Bezoeker</th><td>{{ $visit->visitor_name }}@if ($visit->company) ({{ $visit->company }})@endif</td></tr>
                <tr><th>Reden</th><td>{{ $visit->reason }}</td></tr>
                <tr><th>Badge</th><td>{{ $visit->badge_number ?? '—' }}</td></tr>
                <tr><th>Begeleider</th><td>{{ $visit->escort?->name ?? '—' }}</td></tr>
                <tr><th>Aangemeld</th><td>{{ $visit->checked_in_at->format('d-m-Y H:i') }}</td></tr>
                <tr><th>Afgemeld</th><td>{{ $visit->checked_out_at->format('d-m-Y H:i') }}</td></tr>
            </table>

            @include('pdf.partials.dcim', ['racks' => $data['racks']])
            @break
    @endswitch
</body>
</html>
