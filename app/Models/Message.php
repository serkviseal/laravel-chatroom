<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'body',
        'type',
        'attachment_path',
        'user_id',
        'room_id',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
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

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    public function reactionsGrouped(): array
    {
        return $this->reactions()
            ->selectRaw('emoji, count(*) as count, group_concat(user_id) as user_ids')
            ->groupBy('emoji')
            ->get()
            ->map(fn ($r) => [
                'emoji' => $r->emoji,
                'count' => $r->count,
                'user_ids' => array_map('intval', explode(',', $r->user_ids)),
            ])
            ->values()
            ->toArray();
    }
}
