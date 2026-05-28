<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @property-read Workspace|null $workspace
 * @property-read User|null $uploader
 */
class StoredFile extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $table = 'files';

    protected $fillable = [
        'workspace_id',
        'uploader_id',
        'channel_id',
        'message_id',
        'filename',
        'disk_path',
        'thumbnail_path',
        'mime_type',
        'size_bytes',
        'is_public',
        'shared_token',
        'shared_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'shared_expires_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'channel_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function url(): string
    {
        $disk = config('filesystems.default');
        if ($disk === 'spaces' || $disk === 's3') {
            $cdnUrl = config('filesystems.disks.spaces.url', config('filesystems.disks.s3.url'));

            return $cdnUrl ? $cdnUrl.'/'.$this->disk_path : Storage::disk($disk)->url($this->disk_path);
        }

        return Storage::disk($disk)->url($this->disk_path);
    }

    public function thumbnailUrl(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }
        $disk = config('filesystems.default');
        if ($disk === 'spaces' || $disk === 's3') {
            $cdnUrl = config('filesystems.disks.spaces.url', config('filesystems.disks.s3.url'));

            return $cdnUrl ? $cdnUrl.'/'.$this->thumbnail_path : Storage::disk($disk)->url($this->thumbnail_path);
        }

        return Storage::disk($disk)->url($this->thumbnail_path);
    }

    public function shareLink(): array
    {
        $token = Str::random(32);
        $expiresAt = now()->addDays(7);
        $this->update([
            'shared_token' => $token,
            'shared_expires_at' => $expiresAt,
        ]);

        return [
            'url' => url("/files/shared/{$token}"),
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1024, 1).' KB';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'workspace_id' => $this->workspace_id,
            'uploader_id' => $this->uploader_id,
        ];
    }
}
