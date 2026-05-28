<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

/** @property-read Workspace|null $workspace */
class Room extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'description',
        'workspace_id',
        'type',
        'is_archived',
        'topic',
        'source',
        'is_inbox_item',
        'contact_metadata',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'is_inbox_item' => 'boolean',
        'contact_metadata' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function files(): HasMany
    {
        return $this->hasMany(StoredFile::class, 'channel_id');
    }

    public function pinnedMessages(): HasMany
    {
        return $this->hasMany(Message::class)->where('is_pinned', true);
    }

    public function join(User $user): void
    {
        if (! $this->users()->where('users.id', $user->id)->exists()) {
            $this->users()->attach($user);
        }
    }

    public function leave(User $user): void
    {
        $this->users()->detach($user);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('type', 'public');
    }

    public function scopeForWorkspace(Builder $query, int $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description ?? '',
            'workspace_id' => $this->workspace_id,
            'type' => $this->type,
        ];
    }
}
