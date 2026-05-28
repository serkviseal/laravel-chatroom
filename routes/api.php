<?php

use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ThreadController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Public health check (used by Docker/load balancer)
Route::get('/health', [HealthController::class, 'check']);

// Public shared-file download
Route::get('/files/shared/{token}', [FileController::class, 'shared']);

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth user
    Route::get('/user', [UserController::class, 'me']);
    Route::post('/user', [UserController::class, 'update']);
    Route::patch('/user/status', [UserController::class, 'updateStatus']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/count', [NotificationController::class, 'count']);
    Route::post('/notifications/read', [NotificationController::class, 'markRead']);

    // Workspaces
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show']);
    Route::put('/workspaces/{workspace}', [WorkspaceController::class, 'update']);
    Route::post('/workspaces/{workspace}/join', [WorkspaceController::class, 'join']);
    Route::post('/workspaces/{workspace}/leave', [WorkspaceController::class, 'leave']);
    Route::get('/workspaces/{workspace}/members', [WorkspaceController::class, 'members']);
    Route::put('/workspaces/{workspace}/members/{userId}/role', [WorkspaceController::class, 'updateMemberRole']);
    Route::delete('/workspaces/{workspace}/members/{userId}', [WorkspaceController::class, 'removeMember']);
    Route::get('/workspaces/{workspace}/storage', [WorkspaceController::class, 'storageStats']);
    Route::get('/workspaces/{workspace}/search', [SearchController::class, 'search']);

    // Files
    Route::get('/workspaces/{workspace}/files', [FileController::class, 'index']);
    Route::post('/workspaces/{workspace}/files', [FileController::class, 'upload']);
    Route::post('/files/{file}/share', [FileController::class, 'share']);
    Route::delete('/files/{file}', [FileController::class, 'destroy']);

    // Channels (rooms)
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::post('/rooms/{room}/join', [RoomController::class, 'join']);
    Route::post('/rooms/{room}/leave', [RoomController::class, 'leave']);

    // Messages
    Route::get('/rooms/{room}/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:60,1');
    Route::put('/messages/{message}', [MessageController::class, 'update']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);
    Route::post('/messages/{message}/react', [MessageController::class, 'react']);
    Route::post('/messages/{message}/pin', [MessageController::class, 'pin']);

    // Threads
    Route::get('/rooms/{room}/messages/{message}/replies', [ThreadController::class, 'index']);
    Route::post('/rooms/{room}/messages/{message}/replies', [ThreadController::class, 'store'])
        ->middleware('throttle:60,1');
});
