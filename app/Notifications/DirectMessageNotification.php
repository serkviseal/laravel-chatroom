<?php

namespace App\Notifications;

use App\Models\DirectMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DirectMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly DirectMessage $dm) {}

    /** @return string[] */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'direct_message',
            'dm_id' => $this->dm->id,
            'sender_name' => $this->dm->sender->name,
            'preview' => mb_substr($this->dm->body ?? '', 0, 100),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sender = $this->dm->sender->name;

        return (new MailMessage)
            ->subject("New message from {$sender}")
            ->greeting("Hi {$notifiable->name}!")
            ->line("{$sender} sent you a direct message:")
            ->line('"'.mb_substr($this->dm->body ?? '', 0, 200).'"')
            ->action('Reply', url('/home'));
    }
}
