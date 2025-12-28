<?php

use App\Http\Controllers\AckController;
use App\Http\Controllers\ClientMessageController;
use App\Http\Controllers\ClientRegistrationController;
use App\Http\Controllers\MessagePublishController;
use App\Http\Controllers\PollController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/clients/register', [ClientRegistrationController::class, 'register']);
    Route::post('/messages/publish', [MessagePublishController::class, 'publish']);

    Route::middleware('auth.client')->group(function () {
        Route::post('/messages/send', [ClientMessageController::class, 'send']);
        Route::get('/messages/poll', [PollController::class, 'poll']);
        Route::post('/messages/ack', [AckController::class, 'ack']);
    });
});
