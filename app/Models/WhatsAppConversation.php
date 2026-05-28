<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Workspace|null $workspace
 * @property-read WhatsAppAccount|null $whatsappAccount
 * @property-read WhatsAppContact|null $contact
 * @property-read Room|null $room
 * @property-read User|null $assignedAgent
 * @property-read Bot|null $assignedBot
 */
class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'workspace_id', 'whatsapp_account_id', 'contact_id', 'room_id',
        'wa_conversation_id', 'status', 'conversation_type',
        'assigned_agent_id', 'assigned_bot_id',
        'window_expires_at', 'first_reply_at', 'resolved_at', 'snoozed_until',
    ];

    protected $casts = [
        'window_expires_at' => 'datetime',
        'first_reply_at' => 'datetime',
        'resolved_at' => 'datetime',
        'snoozed_until' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'contact_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function assignedBot(): BelongsTo
    {
        return $this->belongsTo(Bot::class, 'assigned_bot_id');
    }

    public function isWithinWindow(): bool
    {
        return $this->window_expires_at && $this->window_expires_at->isFuture();
    }

    public function refreshWindow(): void
    {
        $this->update(['window_expires_at' => now()->addHours(24)]);
    }

    public function windowSecondsRemaining(): int
    {
        if (! $this->window_expires_at || $this->window_expires_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInSeconds($this->window_expires_at);
    }
}
