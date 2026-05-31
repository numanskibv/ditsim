<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Installatieplan {{ $ticket->number }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1a1a1a; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        .meta { color: #666; font-size: 11px; margin-bottom: 18px; }
        .section { margin-bottom: 14px; }
        .section h2 { font-size: 13px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin: 0 0 5px; }
        .section .body { white-space: pre-wrap; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 2px solid #16a34a; color: #166534; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Installatieplan</h1>
    <div class="meta">
        Ticket {{ $ticket->number }} — {{ $ticket->title }}<br>
        Status: {{ $plan->status->label() }}
    </div>

    @foreach (\App\Models\InstallationPlan::REQUIRED_FIELDS as $field => $label)
        <div class="section">
            <h2>{{ $label }}</h2>
            <div class="body">{{ $plan->{$field} ?: '—' }}</div>
        </div>
    @endforeach

    <div class="footer">
        Goedgekeurd door {{ $plan->approver?->name ?? 'onbekend' }} op
        {{ optional($plan->approved_at)->format('d-m-Y H:i') ?? '—' }}
    </div>
</body>
</html>
