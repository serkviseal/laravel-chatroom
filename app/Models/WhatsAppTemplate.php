<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppTemplate extends Model
{
    protected $fillable = [
        'workspace_id', 'whatsapp_account_id', 'template_name', 'language',
        'category', 'components', 'status', 'rejection_reason',
    ];

    protected $casts = [
        'components' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function bodyText(): ?string
    {
        foreach ($this->components ?? [] as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                return $component['text'] ?? null;
            }
        }

        return null;
    }
}
