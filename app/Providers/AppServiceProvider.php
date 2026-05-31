<?php

namespace App\Providers;

use App\Enums\Ability;
use App\Enums\Role;
use App\Models\User;
use App\Support\CurrentStudent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One resolver per request/worker decides which student world is in
        // scope, so the global student scope and stamping stay consistent.
        $this->app->singleton(CurrentStudent::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Register the role-based authorization gates.
     *
     * Each ability maps to exactly one role: only a teacher manages scenarios,
     * only a manager approves, a technician executes assignments, and a customer
     * files reports. This is the foundation for the four-eyes principle.
     *
     * Counter-role: a student (technicus) is world-aware. In their own world
     * they execute assignments (execute-tasks). In their partner's world they
     * act as the leidinggevende/klant counter-role (approve-tasks +
     * create-reports) but never execute, so couples sign off for each other
     * without separate accounts and four-eyes stays intact. Other roles keep
     * their plain role-based abilities.
     */
    protected function configureAuthorization(): void
    {
        foreach (Ability::cases() as $ability) {
            Gate::define(
                $ability->value,
                fn (User $user): bool => $this->allowsAbility($user, $ability),
            );
        }
    }

    /**
     * Decide whether the user holds the ability in the world currently in scope.
     */
    protected function allowsAbility(User $user, Ability $ability): bool
    {
        if ($user->role !== Role::Technicus) {
            return $user->hasAbility($ability->value);
        }

        $world = app(CurrentStudent::class)->id();

        return match (true) {
            $ability === Ability::ExecuteTasks => $world === $user->id,
            $ability === Ability::ApproveTasks,
            $ability === Ability::CreateReports => $world !== null && $world === $user->partner_id,
            default => false,
        };
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
