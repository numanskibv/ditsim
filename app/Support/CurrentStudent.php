<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\Scopes\StudentScope;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Resolves which student "world" is currently in scope.
 *
 * Every piece of simulation state belongs to exactly one student (a technicus).
 * This resolver answers a single question — "whose world are we looking at?" —
 * and the {@see StudentScope} global scope uses the answer to
 * isolate one student's data from another's.
 *
 * Resolution order:
 *  1. An explicit override set via {@see self::runFor()} (jobs, commands, seeders).
 *  2. The authenticated technicus themselves (their own world).
 *  3. A shared instructor role's selected student (session `active_student_id`).
 *  4. Otherwise null — meaning "no single world", i.e. see everything.
 */
class CurrentStudent
{
    private const SESSION_KEY = 'active_student_id';

    private ?int $override = null;

    private bool $hasOverride = false;

    /**
     * The id of the student world currently in scope, or null for "all worlds".
     */
    public function id(): ?int
    {
        if ($this->hasOverride) {
            return $this->override;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        if ($user->role === Role::Technicus) {
            // A student defaults to their own world, but may switch to their
            // partner's world to act as the leidinggevende/klant counter-role.
            $selected = $this->selectedStudentId();

            if ($selected !== null && in_array($selected, [$user->id, $user->partner_id], true)) {
                return $selected;
            }

            return $user->id;
        }

        // Shared instructor roles (docent/leidinggevende/klant) act within the
        // student world they selected; with no selection they see everything.
        return $this->selectedStudentId();
    }

    /**
     * Store the student world a shared instructor role wants to act within.
     * Passing null clears the selection ("all worlds" / overview).
     */
    public function setActive(?int $studentId): void
    {
        session([self::SESSION_KEY => $studentId]);
    }

    /**
     * Run the callback with the student world forced to the given id (or null).
     * Restores the previous override afterwards, so it nests safely.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function runFor(?int $studentId, Closure $callback): mixed
    {
        $previousOverride = $this->override;
        $previousHasOverride = $this->hasOverride;

        $this->override = $studentId;
        $this->hasOverride = true;

        try {
            return $callback();
        } finally {
            $this->override = $previousOverride;
            $this->hasOverride = $previousHasOverride;
        }
    }

    /**
     * The student id selected in the session, guarding against contexts (console,
     * queue) where no session store is bound.
     */
    private function selectedStudentId(): ?int
    {
        try {
            $id = session(self::SESSION_KEY);
        } catch (Throwable) {
            return null;
        }

        return $id !== null ? (int) $id : null;
    }
}
