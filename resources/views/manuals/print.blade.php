<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Handleiding</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            color: #1a1a1a; background: #fff; line-height: 1.6;
            max-width: 820px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem;
        }
        .toolbar { margin-bottom: 1.5rem; display: flex; gap: .75rem; }
        .toolbar button, .toolbar a {
            font: inherit; cursor: pointer; border: 1px solid #cbd5e1; background: #f8fafc;
            color: #1a1a1a; padding: .45rem .9rem; border-radius: .5rem; text-decoration: none;
        }
        .toolbar button { background: #2563eb; color: #fff; border-color: #2563eb; }
        h1 { font-size: 1.8rem; margin: 0 0 .75rem; }
        h2 { font-size: 1.35rem; margin: 1.6rem 0 .5rem; }
        h3 { font-size: 1.12rem; margin: 1.25rem 0 .5rem; }
        p, ul, ol, blockquote, table { margin: 0 0 .8rem; }
        ul, ol { padding-left: 1.5rem; }
        li { margin: .2rem 0; }
        a { color: #2563eb; }
        code { background: #f1f5f9; padding: .1rem .35rem; border-radius: .25rem; font-size: .9em; }
        hr { margin: 1.6rem 0; border: 0; border-top: 1px solid #e2e8f0; }
        blockquote { border-left: 3px solid #cbd5e1; padding-left: .9rem; color: #334155; }
        table { width: 100%; border-collapse: collapse; font-size: .92em; }
        th, td { border: 1px solid #cbd5e1; padding: .4rem .6rem; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; }
        @media print {
            body { padding: 0; max-width: none; }
            .toolbar { display: none; }
            h2, h3 { page-break-after: avoid; }
            table, blockquote, pre, li { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Opslaan als PDF</button>
        <a href="{{ route('manuals.index') }}">Terug naar de app</a>
    </div>

    <article>
        {!! $html !!}
    </article>
</body>
</html>
