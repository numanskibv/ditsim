<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStudent;
use Database\Factories\VisitorLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['visitor_name', 'company', 'reason', 'badge_number', 'escort_id', 'created_by'])]
class VisitorLog extends Model
{
    /** @use HasFactory<VisitorLogFactory> */
    use BelongsToStudent, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    /**
     * A visit that has been checked in but not yet checked out.
     */
    public function isOpen(): bool
    {
        return $this->checked_in_at !== null && $this->checked_out_at === null;
    }

    /**
     * The visit only counts as complete evidence (opdracht 6) once both the
     * check-in and the check-out moment are recorded.
     */
    public function isCompleteEvidence(): bool
    {
        return $this->checked_in_at !== null && $this->checked_out_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function escort(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escort_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
