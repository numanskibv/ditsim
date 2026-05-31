@auth
    @php
        $user = auth()->user();
        $isTechnicus = $user->hasRole(\App\Enums\Role::Technicus);
        $world = app(\App\Support\CurrentStudent::class)->id();
        $owner = $world !== null ? \App\Models\User::find($world) : null;
    @endphp

    @if ($world === null && ! $isTechnicus)
        {{-- Shared role without a chosen student: overview, writing is disabled. --}}
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300">
            <span class="font-semibold">{{ __('Overzichtsmodus') }}</span>
            — {{ __('je bekijkt alle studenten. Kies bovenin een Actieve student; acties zijn hier uitgeschakeld.') }}
        </div>
    @elseif ($owner && $world !== $user->id)
        {{-- Acting inside someone else's world (partner or a selected student). --}}
        <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ __('Je werkt in de omgeving van') }}: <span class="font-semibold">{{ $owner->name }}</span>
        </div>
    @endif
@endauth
