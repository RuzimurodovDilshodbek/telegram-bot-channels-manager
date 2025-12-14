<?php

use App\Http\Controllers\Api\VacancyController;
use App\Http\Controllers\Api\TelegramWebhookController;
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

// Telegram Webhook
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

// Vacancy Webhook (from oson-ish-api)
Route::post('/vacancies', [VacancyController::class, 'store'])->name('vacancies.store');

// Vacancy Statistics
Route::get('/vacancies/{vacancyId}/statistics', [VacancyController::class, 'statistics'])->name('vacancies.statistics');
