<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StudentScope;
use App\Models\User;
use App\Support\CurrentStudent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as part of a single student's isolated simulation world.
 *
 * Booting the trait:
 *  - registers the {@see StudentScope} so reads/writes stay within the current
 *    world, and
 *  - stamps `student_id` on create with the resolved world (unless one was set
 *    explicitly, e.g. when provisioning into a specific student's world).
 *
 * @property int|null $student_id
 */
trait BelongsToStudent
{
    public static function bootBelongsToStudent(): void
    {
        static::addGlobalScope(new StudentScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('student_id') === null) {
                $model->setAttribute('student_id', app(CurrentStudent::class)->id());
            }
        });
    }

    /**
     * The student (technicus) whose world this record belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
