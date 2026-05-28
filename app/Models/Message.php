<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property-read User $user
 * @property-read Room $room
 * @property-read Workspace|null $workspace
 * @property-read Message|null $threadRoot
 */
class Message extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'body',
        'type',
        'attachment_path',
        'user_id',
        'room_id',
        'workspace_id',
        'thread_id',
        'is_thread_reply',
        'is_pinned',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
            'is_thread_reply' => 'boolean',
            'is_pinned' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function threadRoot(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'thread_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(StoredFile::class);
    }

    public function scopeThreadRoots(Builder $query): Builder
    {
        return $query->whereNull('thread_id');
    }

    public function replyCount(): int
    {
        return $this->replies()->count();
    }

    public function reactionsGrouped(): array
    {
        return $this->reactions()
            ->selectRaw('emoji, count(*) as count, group_concat(user_id) as user_ids')
            ->groupBy('emoji')
            ->get()
            ->map(fn ($r) => [
                'emoji' => $r->getAttribute('emoji'),
                'count' => $r->getAttribute('count'),
                'user_ids' => array_map('intval', explode(',', (string) $r->getAttribute('user_ids'))),
            ])
            ->values()
            ->toArray();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body ?? '',
            'room_id' => $this->room_id,
            'workspace_id' => $this->workspace_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
