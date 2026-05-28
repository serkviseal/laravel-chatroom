<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Message $message) {}

    /** @return string[] */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'mention',
            'message_id' => $this->message->id,
            'channel_id' => $this->message->room_id,
            'workspace_id' => $this->message->workspace_id,
            'sender_name' => $this->message->user->name,
            'preview' => mb_substr($this->message->body ?? '', 0, 100),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sender = $this->message->user->name;
        $channel = $this->message->room->name;

        return (new MailMessage)
            ->subject("{$sender} mentioned you in #{$channel}")
            ->greeting("Hi {$notifiable->name}!")
            ->line("{$sender} mentioned you in **#{$channel}**:")
            ->line('"'.mb_substr($this->message->body ?? '', 0, 200).'"')
            ->action('View Message', url('/home'));
    }
}
