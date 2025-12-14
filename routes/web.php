<?php

use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Click Tracking Route
|--------------------------------------------------------------------------
*/
Route::get('/track/{trackingCode}', [TrackingController::class, 'track'])->name('tracking.click');
