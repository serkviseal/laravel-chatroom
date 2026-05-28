<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotRule extends Model
{
    protected $fillable = [
        'workspace_id', 'bot_id', 'name', 'trigger_type', 'trigger_value',
        'action_type', 'action_value', 'priority', 'is_active', 'stop_on_match',
    ];

    protected $casts = [
        'action_value' => 'array',
        'is_active' => 'boolean',
        'stop_on_match' => 'boolean',
        'priority' => 'integer',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function matches(string $messageBody, WhatsAppConversation $conversation): bool
    {
        return match ($this->trigger_type) {
            'keyword' => str_contains(strtolower($messageBody), strtolower($this->trigger_value ?? '')),
            'regex' => (bool) @preg_match('/'.$this->trigger_value.'/i', $messageBody),
            'always' => true,
            'first_contact' => $conversation->first_reply_at === null,
            'outside_hours' => ! $conversation->whatsappAccount->withinBusinessHours(),
            'unassigned_timeout' => $conversation->assigned_agent_id === null,
            default => false,
        };
    }
}
