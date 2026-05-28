<?php

namespace App\Providers;

use App\Models\Message;
use App\Models\Room;
use App\Models\StoredFile;
use App\Models\Workspace;
use App\Policies\ChannelPolicy;
use App\Policies\FilePolicy;
use App\Policies\MessagePolicy;
use App\Policies\WorkspacePolicy;
use App\Services\MessageParser;
use App\Services\WorkspaceStorageService;
use Illuminate\Support\Facades\Broadcast;
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
    }

    public function register(): void
    {
        $this->app->singleton(MessageParser::class);
        $this->app->singleton(WorkspaceStorageService::class);
    }
}
