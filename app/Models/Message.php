<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStudent;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Fillable(['to_id', 'ticket_id', 'body'])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use BelongsToStudent, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Stamp the sender and the sent moment on every new message.
     */
    protected static function booted(): void
    {
        static::creating(function (Message $message): void {
            $message->from_id ??= Auth::id();
            $message->sent_at ??= now();
        });
    }

    /**
     * Scope to the conversations a given user takes part in.
     *
     * @param  Builder<Message>  $query
     */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('from_id', $userId)->orWhere('to_id', $userId);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
