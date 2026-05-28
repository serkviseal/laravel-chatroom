<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Bot extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id', 'name', 'avatar_url', 'description',
        'auth_token', 'outgoing_webhook_url', 'subscribed_events',
        'channel_ids', 'is_active', 'provider', 'provider_config',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_events' => 'array',
            'channel_ids'       => 'array',
            'provider_config'   => 'array',
            'is_active'         => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function subscribedTo(string $event): bool
    {
        return in_array($event, $this->subscribed_events ?? []);
    }

    public function allowsChannel(int $channelId): bool
    {
        return $this->channel_ids === null || in_array($channelId, $this->channel_ids);
    }
}
