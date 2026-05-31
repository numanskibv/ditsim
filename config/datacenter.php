<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Standaardwachtwoord voor seed- en aangemaakte accounts
    |--------------------------------------------------------------------------
    |
    | Het wachtwoord waarmee de demo-accounts (docent, technicus, …) en de via
    | "Studenten beheren" aangemaakte studenten worden klaargezet. Lokaal is dit
    | "password"; zet in de cloud een sterk wachtwoord via de omgevingsvariabele
    | DEFAULT_ACCOUNT_PASSWORD. De docent kan zijn eigen wachtwoord daarna
    | wijzigen via Instellingen → Beveiliging.
    |
    */

    'default_password' => env('DEFAULT_ACCOUNT_PASSWORD', 'password'),

    /*
    |--------------------------------------------------------------------------
    | Demodata seeden bij installatie
    |--------------------------------------------------------------------------
    |
    | Bepaalt of `php artisan app:install` de demodata + demo-accounts seedt op
    | een lege database. Lokaal staat dit aan. Zet in de cloud SEED_DEMO=false
    | om schoon te starten: er worden dan geen accounts klaargezet en de
    | EERSTE registratie wordt automatisch de docent (beheerder).
    |
    */

    'seed_demo' => env('SEED_DEMO', true),

];
