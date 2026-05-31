<?php

namespace App\Support;

use App\Models\Device;
use App\Models\DeviceAlert;
use App\Models\InspectionReport;
use App\Models\InstallationPlan;
use App\Models\Message;
use App\Models\Rack;
use App\Models\Scopes\StudentScope;
use App\Models\Ticket;
use App\Models\VisitorLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Operations on a single student's isolated simulation world.
 */
class StudentWorld
{
    /**
     * Every simulation model that belongs to a student world.
     *
     * @var list<class-string<Model>>
     */
    private const MODELS = [
        DeviceAlert::class,
        Device::class,
        InstallationPlan::class,
        Message::class,
        Ticket::class,
        InspectionReport::class,
        VisitorLog::class,
        Rack::class,
    ];

    /**
     * Delete everything in the given student's world, leaving it empty.
     *
     * Runs without the student global scope so it works regardless of who
     * triggers it (e.g. a docent acting from their own context).
     */
    public function wipe(int $studentId): void
    {
        foreach (self::MODELS as $model) {
            $model::withoutGlobalScope(StudentScope::class)
                ->where('student_id', $studentId)
                ->delete();
        }
    }

    /**
     * Delete all simulation/world data across every student (the demo content
     * and any student work), leaving accounts, couples and the scenario library
     * intact. Used by the "Demodata verwijderen" action.
     */
    public function clearAll(): void
    {
        foreach (self::MODELS as $model) {
            $model::withoutGlobalScope(StudentScope::class)->delete();
        }
    }
}
