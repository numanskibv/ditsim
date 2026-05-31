<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Concerns\BelongsToStudent;
use Carbon\CarbonImmutable;
use Database\Factories\TicketFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

#[Fillable(['type', 'title', 'description', 'priority', 'device_id', 'assigned_to'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use BelongsToStudent, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'sla_minutes' => 'integer',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Generate the ticket number on create and keep the SLA target in sync
     * with the priority on every save.
     */
    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            $ticket->number ??= static::generateNumber($ticket->type);
            $ticket->created_by ??= Auth::id();
        });

        static::saving(function (Ticket $ticket): void {
            $ticket->sla_minutes = $ticket->priority->slaMinutes();
        });
    }

    /**
     * Build the next sequential ticket number for the given type and year,
     * e.g. INC-2026-0001 / CHG-2026-0001 / SR-2026-0001.
     */
    public static function generateNumber(TicketType $type): string
    {
        $year = now()->year;

        $sequence = static::where('type', $type->value)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s-%d-%04d', $type->prefix(), $year, $sequence);
    }

    /**
     * The moment by which the ticket should be resolved to stay within SLA.
     */
    public function slaDeadline(): CarbonImmutable
    {
        return $this->created_at->addMinutes($this->sla_minutes);
    }

    /**
     * Whether the ticket is (or was, once closed) resolved within its SLA.
     */
    public function isWithinSla(): bool
    {
        $reference = $this->closed_at ?? now();

        return $reference->lessThanOrEqualTo($this->slaDeadline());
    }

    /**
     * The human readable SLA badge label.
     */
    public function slaStatusLabel(): string
    {
        return $this->isWithinSla() ? 'Binnen SLA' : 'Buiten SLA';
    }

    /**
     * Four-eyes rule: a ticket may only be closed once a checker has been
     * assigned and that checker is not the same person who executed it.
     */
    public function canBeClosed(): bool
    {
        return $this->checked_by !== null && $this->checked_by !== $this->assigned_to;
    }

    /**
     * Close the ticket, enforcing the four-eyes rule.
     *
     * @throws DomainException when no distinct checker is assigned
     */
    public function close(): void
    {
        if (! $this->canBeClosed()) {
            throw new DomainException('Afsluiten kan alleen met een controleur die niet de uitvoerder is.');
        }

        $this->status = TicketStatus::Afgesloten;
        $this->closed_at = now();
        $this->save();
    }

    /**
     * Sign off the ticket as a manager.
     */
    public function markApprovedBy(User $manager): void
    {
        $this->approved_by = $manager->id;
        $this->approved_at = now();
        $this->save();
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * @return HasOne<InstallationPlan, $this>
     */
    public function installationPlan(): HasOne
    {
        return $this->hasOne(InstallationPlan::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest('sent_at');
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
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
