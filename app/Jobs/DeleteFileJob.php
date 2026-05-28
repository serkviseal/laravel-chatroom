<?php

namespace App\Jobs;

use App\Models\StoredFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DeleteFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $diskPath, private readonly ?string $thumbnailPath)
    {
        $this->delay(now()->addSeconds(30));
    }

    public function handle(): void
    {
        $disk = config('filesystems.default');
        Storage::disk($disk)->delete($this->diskPath);
        if ($this->thumbnailPath) {
            Storage::disk($disk)->delete($this->thumbnailPath);
        }
    }
}
