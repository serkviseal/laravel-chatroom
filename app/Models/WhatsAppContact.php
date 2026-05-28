<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppContact extends Model
{
    protected $fillable = [
        'workspace_id', 'whatsapp_account_id', 'phone', 'display_name',
        'profile_name', 'avatar_url', 'locale', 'last_seen_at',
        'opt_in_at', 'opt_out_at', 'meta',
    ];

    protected $casts = [
        'meta'        => 'array',
        'last_seen_at' => 'datetime',
        'opt_in_at'   => 'datetime',
        'opt_out_at'  => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'contact_id');
    }

    public function hasOptedOut(): bool
    {
        return $this->opt_out_at !== null && ($this->opt_in_at === null || $this->opt_out_at->gt($this->opt_in_at));
    }

    public function getDisplayNameAttribute($value): string
    {
        return $value ?: $this->profile_name ?: $this->phone;
    }
}
