<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DownloadWhatsAppMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private int    $accountId,
        private string $mediaId,
        private int    $conversationId
    ) {}

    public function handle(): void
    {
        $account      = WhatsAppAccount::find($this->accountId);
        $conversation = WhatsAppConversation::find($this->conversationId);

        if (!$account || !$conversation) {
            return;
        }

        $wa   = WhatsAppService::for($account);
        $path = $wa->downloadMedia($this->mediaId);

        if ($path) {
            Message::where('room_id', $conversation->room_id)
                ->where('body', '[media]')
                ->latest()
                ->first()
                ?->update(['body' => "[media:{$path}]"]);
        }
    }
}
