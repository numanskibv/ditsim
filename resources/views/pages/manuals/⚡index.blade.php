<?php

use App\Enums\Ability;
use App\Support\Manuals;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * In-app reader for the role manuals (docent only). Each manual can be opened
 * as a clean print version to hand out on paper.
 */
new #[Title('Handleidingen')] class extends Component
{
    public string $slug = 'docent';

    public function mount(): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        if (! app(Manuals::class)->exists($this->slug)) {
            $this->slug = $this->manuals->first()['slug'] ?? 'docent';
        }
    }

    /**
     * @return Collection<int, array{slug: string, label: string}>
     */
    #[Computed]
    public function manuals(): Collection
    {
        return app(Manuals::class)->all();
    }

    #[Computed]
    public function html(): ?string
    {
        return app(Manuals::class)->html($this->slug);
    }

    public function select(string $slug): void
    {
        if (app(Manuals::class)->exists($slug)) {
            $this->slug = $slug;
            unset($this->html);
        }
    }
}; ?>

<section class="w-full max-w-4xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">{{ __('Handleidingen') }}</flux:heading>
            <flux:subheading>{{ __('Lees de handleidingen of maak er een printversie van om uit te delen.') }}</flux:subheading>
        </div>

        <flux:button :href="route('manuals.print', ['slug' => $slug])" target="_blank" icon="printer" variant="primary" data-test="print-manual">
            {{ __('Printversie openen') }}
        </flux:button>
    </div>

    {{-- Tabs --}}
    <div class="flex flex-wrap gap-2">
        @foreach ($this->manuals as $manual)
            <flux:button
                size="sm"
                :variant="$manual['slug'] === $slug ? 'primary' : 'filled'"
                wire:click="select('{{ $manual['slug'] }}')"
                wire:key="tab-{{ $manual['slug'] }}"
                data-test="tab-{{ $manual['slug'] }}"
            >
                {{ $manual['label'] }}
            </flux:button>
        @endforeach
    </div>

    {{-- Rendered manual --}}
    <flux:card>
        <article class="manual">
            {!! $this->html !!}
        </article>
    </flux:card>
</section>

@once
    <style>
        .manual { line-height: 1.6; }
        .manual h1 { font-size: 1.6rem; font-weight: 700; margin: 0 0 .75rem; }
        .manual h2 { font-size: 1.3rem; font-weight: 700; margin: 1.5rem 0 .5rem; }
        .manual h3 { font-size: 1.1rem; font-weight: 600; margin: 1.25rem 0 .5rem; }
        .manual p, .manual ul, .manual ol, .manual blockquote, .manual table { margin: 0 0 .75rem; }
        .manual ul, .manual ol { padding-left: 1.4rem; }
        .manual ul { list-style: disc; }
        .manual ol { list-style: decimal; }
        .manual li { margin: .2rem 0; }
        .manual a { color: #2563eb; text-decoration: underline; }
        .manual code { background: rgba(127,127,127,.18); padding: .1rem .35rem; border-radius: .25rem; font-size: .9em; }
        .manual hr { margin: 1.5rem 0; border: 0; border-top: 1px solid rgba(127,127,127,.3); }
        .manual blockquote { border-left: 3px solid rgba(127,127,127,.4); padding-left: .9rem; color: inherit; opacity: .9; }
        .manual table { width: 100%; border-collapse: collapse; font-size: .92em; }
        .manual th, .manual td { border: 1px solid rgba(127,127,127,.35); padding: .4rem .6rem; text-align: left; vertical-align: top; }
        .manual th { background: rgba(127,127,127,.12); }
    </style>
@endonce
