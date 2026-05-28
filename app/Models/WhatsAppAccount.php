<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppAccount extends Model
{
    protected $fillable = [
        'workspace_id', 'display_name', 'phone_number_id', 'waba_id',
        'access_token', 'verify_token', 'webhook_secret',
        'business_hours', 'welcome_template', 'is_active',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['access_token', 'webhook_secret'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(WhatsAppContact::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(WhatsAppTemplate::class);
    }

    public function withinBusinessHours(): bool
    {
        $hours = $this->business_hours;
        if (empty($hours)) {
            return true;
        }

        $now = now();
        $day = strtolower($now->format('l'));
        $dayConfig = $hours[$day] ?? null;

        if (!$dayConfig || !($dayConfig['enabled'] ?? false)) {
            return false;
        }

        $start = $now->copy()->setTimeFromTimeString($dayConfig['start'] ?? '09:00');
        $end   = $now->copy()->setTimeFromTimeString($dayConfig['end'] ?? '17:00');

        return $now->between($start, $end);
    }
}
