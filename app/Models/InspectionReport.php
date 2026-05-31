<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStudent;
use Database\Factories\InspectionReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inspector_id', 'date', 'items'])]
class InspectionReport extends Model
{
    /** @use HasFactory<InspectionReportFactory> */
    use BelongsToStudent, HasFactory;

    /**
     * The fixed control points every inspection round must cover.
     *
     * @var array<string, string>
     */
    public const CONTROL_POINTS = [
        'koeling' => 'Koeling',
        'stroom' => 'Stroom (UPS/PDU)',
        'brandveiligheid' => 'Brandveiligheid',
        'security' => 'Security/toegang',
        'racks' => 'Racks/kabelmanagement',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'items' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
