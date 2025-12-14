<?php

namespace App\Http\Controllers;

use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class TrackingController extends Controller
{
    protected TrackingService $tracking;

    public function __construct(TrackingService $tracking)
    {
        $this->tracking = $tracking;
    }

    /**
     * Track click and redirect to vacancy
     */
    public function track(Request $request, string $trackingCode): RedirectResponse
    {
        $targetUrl = $this->tracking->trackClick($trackingCode, $request);

        if (!$targetUrl) {
            // If tracking code not found, redirect to homepage or show 404
            abort(404, 'Link topilmadi');
        }

        // Redirect to the original vacancy URL
        return redirect($targetUrl, config('tracking.redirect.status_code', 301));
    }
}
