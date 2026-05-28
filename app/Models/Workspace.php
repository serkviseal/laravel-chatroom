<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'avatar_path',
        'owner_id',
        'storage_quota_mb',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role', 'joined_at', 'last_seen_at')
            ->withTimestamps();
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(StoredFile::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(WorkspacePreference::class);
    }

    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }

    public function whatsappAccounts(): HasMany
    {
        return $this->hasMany(WhatsAppAccount::class);
    }

    public function botRules(): HasMany
    {
        return $this->hasMany(BotRule::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('members', fn ($q) => $q->where('users.id', $user->id));
    }

    public function userRole(User $user): ?string
    {
        return $this->members()
            ->where('users.id', $user->id)
            ->value('role');
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->exists();
    }

    public function storageUsedBytes(): int
    {
        return (int) $this->files()->whereNull('deleted_at')->sum('size_bytes');
    }

    public function storageUsedMb(): float
    {
        return round($this->storageUsedBytes() / 1024 / 1024, 2);
    }

    public function storageQuotaBytes(): int
    {
        return $this->storage_quota_mb * 1024 * 1024;
    }

    public function avatarUrl(): string
    {
        return $this->avatar_path
            ? asset('storage/'.$this->avatar_path)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=6366f1&color=fff&size=128';
    }
}
