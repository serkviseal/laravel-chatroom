<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/** @property Pivot $pivot */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_user')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function directMessagesSent(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'sender_id');
    }

    public function directMessagesReceived(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'recipient_id');
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot('role', 'joined_at', 'last_seen_at');
    }

    public function workspacePreferences(): HasMany
    {
        return $this->hasMany(WorkspacePreference::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(StoredFile::class, 'uploader_id');
    }

    public function addRoom(Room $room): void
    {
        $this->rooms()->attach($room);
    }

    public function hasJoined(int $roomId): bool
    {
        return $this->rooms()->where('rooms.id', $roomId)->exists();
    }

    public function isMemberOf(Workspace $workspace): bool
    {
        return $this->workspaces()->where('workspaces.id', $workspace->id)->exists();
    }

    public function roleIn(Workspace $workspace): ?string
    {
        return $this->workspaces()->where('workspaces.id', $workspace->id)->value('role');
    }

    public function preferenceIn(Workspace $workspace): WorkspacePreference
    {
        return WorkspacePreference::firstOrCreate(
            ['user_id' => $this->id, 'workspace_id' => $workspace->id],
            ['notification_preference' => 'all', 'status' => 'offline']
        );
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/'.$this->avatar)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=6366f1&color=fff';
    }
}
