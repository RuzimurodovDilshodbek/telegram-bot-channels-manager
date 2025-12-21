<?php

use App\Http\Controllers\Api\VacancyController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\PollBotWebhookController;
use App\Http\Controllers\Api\IpCollectorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Webhook Routes (No Authentication)
|--------------------------------------------------------------------------
*/

// Telegram Webhook (Vacancy Bot)
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

// Poll Bot Webhook
Route::post('/poll-bot/webhook', [PollBotWebhookController::class, 'handle'])->name('poll-bot.webhook');
Route::get('/poll-bot/webhook/info', [PollBotWebhookController::class, 'info'])->name('poll-bot.webhook.info');
Route::post('/poll-bot/webhook/set', [PollBotWebhookController::class, 'setWebhook'])->name('poll-bot.webhook.set');
Route::post('/poll-bot/webhook/remove', [PollBotWebhookController::class, 'removeWebhook'])->name('poll-bot.webhook.remove');

// Vacancy Webhook (from oson-ish-api)
Route::post('/vacancies', [VacancyController::class, 'store'])->name('vacancies.store');

// Vacancy Statistics
Route::get('/vacancies/{vacancyId}/statistics', [VacancyController::class, 'statistics'])->name('vacancies.statistics');

// IP Collector for Poll Bot
Route::get('/ip-collector', [IpCollectorController::class, 'show'])->name('ip-collector.show');
Route::post('/collect-ip', [IpCollectorController::class, 'collect'])->name('ip-collector.collect');
