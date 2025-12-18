<?php

use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // Redirect to admin panel
    // If authenticated, go to dashboard, otherwise to login
    return redirect('/admin');
});

/*
|--------------------------------------------------------------------------
| Click Tracking Route
|--------------------------------------------------------------------------
*/
Route::get('/track/{trackingCode}', [TrackingController::class, 'track'])->name('tracking.click');
