<?php

namespace App\Services;

use App\Models\Workspace;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WorkspaceStorageService
{
    public function usedBytes(Workspace $workspace): int
    {
        return (int) $workspace->files()->whereNull('deleted_at')->sum('size_bytes');
    }

    public function checkQuota(Workspace $workspace, int $newBytes): void
    {
        $used = $this->usedBytes($workspace);
        $quota = $workspace->storageQuotaBytes();

        if ($used + $newBytes > $quota) {
            $usedMb = round($used / 1024 / 1024, 1);
            $quotaMb = $workspace->storage_quota_mb;
            throw new HttpException(413, "Storage quota exceeded ({$usedMb}MB of {$quotaMb}MB used).");
        }
    }

    public function percentUsed(Workspace $workspace): float
    {
        $quota = $workspace->storageQuotaBytes();
        if ($quota === 0) {
            return 0;
        }
        return round($this->usedBytes($workspace) / $quota * 100, 1);
    }
}
