<?php

namespace App\Models\Scopes;

use App\Support\CurrentStudent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Isolates simulation state per student world.
 *
 * When a concrete student world is in scope, queries are limited to that
 * student's rows plus any unattributed (null) rows — the latter act as a shared
 * baseline (demo/seed data, fixtures) visible to everyone. When no single world
 * is resolved (a guest, the console, or a shared instructor viewing "all"), no
 * filter is applied and everything is visible.
 *
 * @see CurrentStudent
 */
class StudentScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $studentId = app(CurrentStudent::class)->id();

        if ($studentId === null) {
            return;
        }

        $column = $model->qualifyColumn('student_id');

        $builder->where(function (Builder $query) use ($column, $studentId): void {
            $query->where($column, $studentId)->orWhereNull($column);
        });
    }
}
