<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Kabelstaat</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1a1a1a; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        .meta { color: #666; font-size: 11px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; font-size: 11px; }
        th { background: #f3f4f6; }
        .empty { color: #666; font-style: italic; }
        .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #ccc; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Kabelstaat</h1>
    <div class="meta">
        {{ $student->name }}{{ $student->student_number ? ' · '.$student->student_number : '' }}<br>
        Gegenereerd op {{ $date->format('d-m-Y H:i') }}
    </div>

    @if ($cables->isEmpty())
        <p class="empty">Nog geen kabels gepatcht.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Nr</th>
                    <th>Van (apparaat : poort)</th>
                    <th>Naar (apparaat : poort)</th>
                    <th>Type</th>
                    <th>Kleur</th>
                    <th>Laatste wijziging</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cables as $cable)
                    <tr>
                        <td><strong>{{ $cable->label }}</strong></td>
                        <td>{{ $cable->fromDevice?->name ?? '?' }} : {{ $cable->from_port }}</td>
                        <td>{{ $cable->toDevice?->name ?? '?' }} : {{ $cable->to_port }}</td>
                        <td>{{ $cable->medium->label() }}</td>
                        <td>{{ $cable->color ?? '—' }}</td>
                        <td>
                            @if ($cable->last_changed_at)
                                {{ $cable->lastChangedBy?->name ?? 'onbekend' }} · {{ $cable->last_changed_at->format('d-m-Y H:i') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Bewijs voor opdracht 1/5 — datacenter-simulatie. Elke kabel logt automatisch wie de
        wijziging deed en wanneer.
    </div>
</body>
</html>
