<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

# CLAUDE.md — Datacenter Simulatie (DITT keuzedeel)

> Plaats dit bestand in de root van je Laravel-project. Claude Code leest het automatisch bij elke sessie.
> Houd het kort en feitelijk; dit is de bron van waarheid voor het project.

## Wat is dit project?

Een Laravel 13 web-applicatie die een datacenter-werkomgeving simuleert. Het doel is dat MBO-niveau-4
studenten (opleiding Datacenter IT techniek, keuzedeel K1171) de zes praktijkopdrachten kunnen uitvoeren
in een gesimuleerde omgeving, omdat echte praktijkmiddelen ontbreken. Elke handeling in de app moet
verifieerbaar bewijs opleveren voor het examenportfolio.

**Kernprincipe: elk scherm moet een screenshot/PDF opleveren die een beoordelingscriterium afdekt.**
Bouw geen feature die geen bewijs oplevert. Elk model dat bewijs vormt, logt automatisch een tijdstempel
en de naam van de uitvoerende gebruiker.

## Tech stack (niet afwijken zonder overleg)

- Laravel 13 (PHP 8.3+)
- Livewire (frontend; geen Vue/React/Inertia)
- Blade + Tailwind CSS voor de UI
- SQLite als database (lesomgeving, geen externe DB nodig)
- Laravel Reverb voor realtime websocket-updates (monitoring-dashboard en meldingen)
- barryvdh/laravel-dompdf voor PDF-bewijsexport
- Pest voor tests

## Rollen (autorisatie)

Gebruik Laravel Policies/Gates. Vier rollen op het User-model (kolom `role`):

- `technicus`  — de student; voert opdrachten uit
- `leidinggevende` — keurt installatieplannen en tickets goed, tekent af
- `klant`      — maakt alarmmeldingen aan, bezoekt het datacenter
- `docent`     — beheert scenario's, triggert storingen, ziet alles (admin)

## Modules en welk criterium ze afdekken

| Module | Dekt opdracht | Belangrijkste bewijs |
|--------|---------------|----------------------|
| Rollen & users | alle | n.v.t. (fundament) |
| DCIM (racks + devices) | 1, 5, 6 | rackoverzicht + auto-gelogde wijzigingen |
| Ticketsysteem | 1, 2, 4, 5 | ticket met datum, naam, akkoord leidinggevende |
| Monitoring + meldingen | 2, 3, 4 | dashboard-screenshot, incidentlog |
| Installatieplan-bouwer | 1 | goedgekeurd plan als PDF |
| Toegangsregister | 6 (CRUCIAAL) | aan- EN afmelden met tijdstempel |
| Inspectieronde | 3 | ingevuld inspectierapport |
| Communicatielog | 3, 5 | bericht naar collega/melder |
| Bewijs-export | alle | samengestelde PDF per opdracht |

## Datamodel (kern)

- **User**: name, email, password, role
- **Rack**: name (bv R03), location (bv DC-Utrecht), height_u (bv 42)
- **Device**: name, type (server/switch/koelunit/ups/sensor), rack_id, u_start, u_end,
  serial, status (actief/waarschuwing/storing/offline), cpu, temp, metrics (json),
  last_changed_at, last_changed_by
- **Ticket**: number, type (incident/service_request/change), reporter, description,
  priority (P1/P2/P3), sla_minutes, diagnosis, action, device_id, assigned_to,
  status (open/in_behandeling/wachten_op_controle/afgesloten), checked_by,
  closed_at, approved_by, approved_at
- **InstallationPlan**: ticket_id, work, materials (json), means (json), colleague,
  security_measures, status (concept/goedgekeurd/afgekeurd), approved_by, approved_at
- **VisitorLog**: visitor_name, company, reason, badge_number, escort_id (user),
  checked_in_at, checked_out_at
- **InspectionReport**: inspector_id, date, items (json: punt/status/waarneming)
- **Message**: from_id, to_id, ticket_id (nullable), body, sent_at
- **Scenario** (docent): naam, beschrijving, acties (json: welk device naar welke status, met delay)

## Belangrijke regels voor de bewijsvoering

1. **SLA-bewaking**: priority bepaalt sla_minutes (P1=60, P2=240, P3=480). Toon bij elk ticket of het
   binnen of buiten SLA is afgehandeld (closed_at vs created_at + sla_minutes).
2. **Vier-ogen / security**: een ticket kan alleen naar `afgesloten` als `checked_by` is ingevuld
   (een andere gebruiker dan assigned_to). Dit dekt de cruciale security-criteria.
3. **Auto-logging**: elke wijziging aan een Device of de DCIM zet last_changed_at + last_changed_by.
4. **Predictive (opdracht 4)**: een Device kan een metric hebben die over tijd oploopt; bij een
   drempel komt eerst status `waarschuwing` (vroeg signaal) vóór `storing`. De student moet kunnen
   ingrijpen in de waarschuwingsfase.
5. **Toegangsregister (opdracht 6)**: checked_in_at en checked_out_at zijn beide verplicht voor
   geldig bewijs; toon ze allebei in de export.

## Codeconventies

- Livewire-componenten in `app/Livewire`, Blade-views in `resources/views/livewire`.
- Eloquent-modellen met expliciete `$fillable` of de nieuwe PHP-attribute-syntax (Laravel 13), consistent kiezen.
- Schrijf bij elke module minstens één Pest-test op het hoofdgedrag.
- Nederlandse labels in de UI (studenten zijn Nederlandstalig); Engelse code/variabelen.
- Seed realistische demodata (klant MediCloud BV, datacenter DC-Utrecht) zodat schermen meteen gevuld zijn.

## Bouwvolgorde (niet vooruitlopen)

1. Rollen & users  2. DCIM  3. Tickets  4. Monitoring+meldingen  5. Installatieplan+PDF
6. Toegangsregister+inspectie  7. Bewijs-export

Bouw één module tegelijk, met tests, en stop voor review voordat je aan de volgende begint.
