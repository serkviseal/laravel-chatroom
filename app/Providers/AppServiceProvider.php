<?php

namespace App\Providers;

use App\Events\MessageCreated;
use App\Http\Controllers\Api\BotController;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Message;
use App\Models\Room;
use App\Models\StoredFile;
use App\Models\Workspace;
use App\Models\WhatsAppConversation;
use App\Policies\ChannelPolicy;
use App\Policies\FilePolicy;
use App\Policies\MessagePolicy;
use App\Policies\WorkspacePolicy;
use App\Services\BotRuleEngine;
use App\Services\MessageParser;
use App\Services\WhatsAppService;
use App\Services\WorkspaceStorageService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(Room::class, ChannelPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(StoredFile::class, FilePolicy::class);

        // When a human agent posts in a WhatsApp-sourced room, send the message via WA
        Event::listen(MessageCreated::class, function (MessageCreated $event) {
            $message = $event->message;

            // Only outbound messages from human agents (user_id set, not already from WA)
            if (!$message->user_id || $message->origin === 'whatsapp') {
                return;
            }

            $conversation = WhatsAppConversation::where('room_id', $message->room_id)
                ->whereIn('status', ['open', 'pending'])
                ->first();

            if ($conversation) {
                SendWhatsAppMessage::dispatch($message->id, $conversation->id)
                    ->onQueue('whatsapp-outbound');
            }
        });

        // Dispatch to any webhook-subscribed bots when a message is created
        Event::listen(MessageCreated::class, function (MessageCreated $event) {
            BotController::dispatchToSubscribedBots($event->message);
        });
    }

    public function register(): void
    {
        $this->app->singleton(MessageParser::class);
        $this->app->singleton(WorkspaceStorageService::class);
    }
}
