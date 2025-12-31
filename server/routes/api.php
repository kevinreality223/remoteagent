<?php

use App\Http\Controllers\AckMessagesController;
use App\Http\Controllers\ClientMessageController;
use App\Http\Controllers\ClientRegistrationController;
use App\Http\Controllers\MessagePublishController;
use App\Http\Controllers\OperatorClientsController;
use App\Http\Controllers\OperatorClientMessagesController;
use App\Http\Controllers\PollMessagesController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/clients/register', ClientRegistrationController::class);

    Route::post('/messages/publish', MessagePublishController::class);

    Route::middleware([\App\Http\Middleware\OperatorTokenAuth::class])->prefix('operators')->group(function () {
        Route::get('/clients', [OperatorClientsController::class, 'index']);
        Route::get('/clients/{client}/messages', [OperatorClientMessagesController::class, 'index']);
    });

    Route::middleware(['throttle:api', \App\Http\Middleware\ClientTokenAuth::class])->group(function () {
        Route::post('/messages/send', [ClientMessageController::class, 'send']);
        Route::get('/messages/poll', PollMessagesController::class)->middleware('throttle:poll');
        Route::post('/messages/ack', AckMessagesController::class);
    });
});
