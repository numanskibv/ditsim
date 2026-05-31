<?php

namespace App\Models;

use App\Enums\InstallationPlanStatus;
use App\Models\Concerns\BelongsToStudent;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

#[Fillable(['werkzaamheden', 'materialen', 'middelen', 'betrokken_collega', 'security_fysiek', 'security_virtueel'])]
class InstallationPlan extends Model
{
    use BelongsToStudent;

    /**
     * The mandatory sections, mapped from database column to display label.
     * Security counts as one section with a physical and a virtual part.
     *
     * @var array<string, string>
     */
    public const REQUIRED_FIELDS = [
        'werkzaamheden' => 'Werkzaamheden',
        'materialen' => 'Materialenlijst',
        'middelen' => 'Middelenlijst',
        'betrokken_collega' => 'Betrokken collega',
        'security_fysiek' => 'Securitymaatregelen (fysiek)',
        'security_virtueel' => 'Securitymaatregelen (virtueel)',
    ];

    /**
     * Default attribute values so a fresh plan always has a status.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'concept',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InstallationPlanStatus::class,
            'ready_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * The labels of the mandatory sections that are still empty.
     *
     * @return list<string>
     */
    public function missingSections(): array
    {
        return (new Collection(self::REQUIRED_FIELDS))
            ->filter(fn (string $label, string $field): bool => blank($this->{$field}))
            ->values()
            ->all();
    }

    /**
     * Whether every mandatory section has been filled in.
     */
    public function isComplete(): bool
    {
        return $this->missingSections() === [];
    }

    public function isApproved(): bool
    {
        return $this->status === InstallationPlanStatus::Goedgekeurd;
    }

    /**
     * Mark the plan ready for approval. Refuses while a section is empty.
     *
     * @throws DomainException when a mandatory section is missing
     */
    public function markReady(): void
    {
        if (! $this->isComplete()) {
            throw new DomainException('Het plan kan niet als klaar worden gemarkeerd: een verplichte sectie is leeg.');
        }

        $this->ready_at = now();
        $this->save();
    }

    /**
     * Approve the plan as a manager.
     */
    public function approveBy(User $manager): void
    {
        $this->forceFill([
            'status' => InstallationPlanStatus::Goedgekeurd,
            'approved_by' => $manager->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ])->save();
    }

    /**
     * Reject the plan as a manager, recording the reason.
     */
    public function rejectBy(User $manager, string $reason): void
    {
        $this->forceFill([
            'status' => InstallationPlanStatus::Afgekeurd,
            'approved_by' => $manager->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ])->save();
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
