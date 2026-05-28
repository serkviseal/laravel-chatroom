<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $notification_preference
 * @property string $status
 * @property string|null $status_emoji
 * @property string|null $status_text
 */
class WorkspacePreference extends Model
{
    protected $fillable = [
        'user_id',
        'workspace_id',
        'notification_preference',
        'status',
        'status_emoji',
        'status_text',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
