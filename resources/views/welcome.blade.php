<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">
        {{-- Navigation --}}
        <header class="sticky top-0 z-10 border-b border-zinc-200/70 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <x-app-logo href="{{ route('home') }}" />

                <nav class="flex items-center gap-3">
                    @auth
                        <flux:button :href="route('dashboard')" variant="primary" icon="layout-grid" wire:navigate>
                            {{ __('Naar dashboard') }}
                        </flux:button>
                    @else
                        <flux:button :href="route('login')" variant="ghost">{{ __('Inloggen') }}</flux:button>
                        @if (Route::has('register'))
                            <flux:button :href="route('register')" variant="primary">{{ __('Aan de slag') }}</flux:button>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="relative overflow-hidden">
                <div class="pointer-events-none absolute inset-0 -z-10 opacity-60">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/10 dark:stroke-zinc-100/10" />
                    <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-zinc-300 to-transparent dark:via-zinc-700"></div>
                </div>

                <div class="mx-auto max-w-3xl px-6 py-24 text-center sm:py-32">
                    <div class="mx-auto mb-8 flex size-16 items-center justify-center rounded-2xl bg-accent-content text-accent-foreground shadow-lg shadow-zinc-900/10">
                        <x-app-logo-icon class="size-9" />
                    </div>

                    <flux:badge color="zinc" size="sm">{{ __('Leeromgeving voor ICT-beheer') }}</flux:badge>

                    <h1 class="mt-5 text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                        {{ __('Beheer een datacenter — zonder risico') }}
                    </h1>

                    <p class="mx-auto mt-5 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                        {{ __('Een realistische simulatie van de volledige ICT-beheerketen: van rackbeheer en tickets met SLA-bewaking tot realtime monitoring, vier-ogen-controle en kant-en-klaar portfoliobewijs.') }}
                    </p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        @auth
                            <flux:button :href="route('dashboard')" variant="primary" icon="layout-grid" wire:navigate>
                                {{ __('Naar dashboard') }}
                            </flux:button>
                        @else
                            <flux:button :href="route('login')" variant="primary">{{ __('Inloggen') }}</flux:button>
                            @if (Route::has('register'))
                                <flux:button :href="route('register')" variant="ghost">{{ __('Account aanmaken') }}</flux:button>
                            @endif
                        @endauth
                    </div>

                    {{-- Live-status hint --}}
                    <div class="mt-10 flex items-center justify-center gap-4 text-sm text-zinc-500">
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-green-500"></span>{{ __('Actief') }}</span>
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-orange-500"></span>{{ __('Waarschuwing') }}</span>
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-red-500"></span>{{ __('Storing') }}</span>
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-zinc-400"></span>{{ __('Offline') }}</span>
                    </div>
                </div>
            </section>

            {{-- Features --}}
            <section class="mx-auto max-w-6xl px-6 pb-24">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $features = [
                            ['icon' => 'server-stack', 'title' => 'DCIM-rackbeheer', 'text' => 'Visueel rackoverzicht met devices op hun U-positie, gekleurd naar status en met audit-trail.'],
                            ['icon' => 'ticket', 'title' => 'Tickets & SLA', 'text' => 'Incidenten en wijzigingen met prioriteit, SLA-bewaking en vier-ogen-afsluiting.'],
                            ['icon' => 'signal', 'title' => 'Realtime monitoring', 'text' => 'NOC-dashboard met live metrics en alarmen via Laravel Reverb — zonder pagina-refresh.'],
                            ['icon' => 'beaker', 'title' => 'Docent-scenario\'s', 'text' => 'Trigger realistische storingen en geplande scenario\'s voor oefeningen.'],
                            ['icon' => 'clipboard-document-list', 'title' => 'Installatieplan', 'text' => 'Gestructureerd plan met goedkeuringsflow en PDF-export van het bewijs.'],
                            ['icon' => 'document-arrow-down', 'title' => 'Portfoliobewijs', 'text' => 'Genereer per opdracht één nette PDF met de echte bewijsstukken.'],
                        ];
                    @endphp

                    @foreach ($features as $feature)
                        <div class="rounded-xl border border-zinc-200 bg-white p-6 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-accent-content text-accent-foreground">
                                <flux:icon :icon="$feature['icon']" class="size-5" />
                            </div>
                            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">{{ __($feature['title']) }}</h3>
                            <p class="mt-1.5 text-sm text-zinc-600 dark:text-zinc-400">{{ __($feature['text']) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-8 text-sm text-zinc-500 sm:flex-row">
                <div class="flex items-center gap-2">
                    <x-app-logo-icon class="size-5 text-zinc-400" />
                    <span>{{ config('app.name', 'Datacenter Sim') }}</span>
                </div>
                <span>{{ __('Gebouwd met Laravel, Livewire, Flux & Reverb.') }}</span>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
