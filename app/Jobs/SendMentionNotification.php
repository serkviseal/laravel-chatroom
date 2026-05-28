<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\User;
use App\Notifications\MentionNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMentionNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Message $message,
        private readonly string $mentionedName,
    ) {}

    public function handle(): void
    {
        $user = User::where('name', $this->mentionedName)->first();
        if (! $user || $user->id === $this->message->user_id) {
            return;
        }

        $pref = $user->preferenceIn($this->message->workspace ?? new \App\Models\Workspace);
        if ($pref->notification_preference === 'nothing') {
            return;
        }

        $user->notify(new MentionNotification($this->message));
    }
}
