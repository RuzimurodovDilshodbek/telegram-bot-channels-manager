<?php

namespace App\Services;

use App\Models\ChannelPost;
use App\Models\VacancyClick;
use App\Jobs\RecordVacancyClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrackingService
{
    /**
     * Track click and redirect
     */
    public function trackClick(string $trackingCode, Request $request): ?string
    {
        $channelPost = ChannelPost::where('unique_tracking_code', $trackingCode)->first();

        if (!$channelPost) {
            Log::warning('Tracking code not found', ['code' => $trackingCode]);
            return null;
        }

        // Get target URL from osonIshVacancy or fallback to botVacancy
        $targetUrl = $this->getTargetUrl($channelPost);

        if (!$targetUrl) {
            Log::error('No target URL found for tracking code', ['code' => $trackingCode]);
            return null;
        }

        // Check if bot
        if ($this->isBot($request)) {
            if (config('tracking.bot_detection.exclude_bots', true)) {
                Log::info('Bot detected - click not tracked', ['user_agent' => $request->userAgent()]);
                return $targetUrl;
            }
        }

        // Check rate limit
        if ($this->isRateLimited($request)) {
            Log::warning('Rate limit exceeded', ['ip' => $request->ip()]);
            return $targetUrl;
        }

        // Check deduplication
        if (!$this->shouldRecordClick($channelPost, $request)) {
            Log::info('Duplicate click - not recorded', [
                'tracking_code' => $trackingCode,
                'ip' => $request->ip(),
            ]);
            return $targetUrl;
        }

        // Dispatch job to record click
        RecordVacancyClick::dispatch($channelPost->id, $this->getClickData($request));

        // Increment counter immediately
        $channelPost->incrementClicksCount();

        return $targetUrl;
    }

    /**
     * Check if should record click (deduplication)
     */
    protected function shouldRecordClick(ChannelPost $channelPost, Request $request): bool
    {
        if (!config('tracking.deduplication.enabled', true)) {
            return true;
        }

        $key = $this->getDeduplicationKey($channelPost, $request);
        $window = config('tracking.deduplication.window_seconds', 300);

        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, true, $window);

        return true;
    }

    /**
     * Get deduplication cache key
     */
    protected function getDeduplicationKey(ChannelPost $channelPost, Request $request): string
    {
        $method = config('tracking.deduplication.method', 'ip+user_agent');

        $key = "click_dedup:{$channelPost->id}:";

        switch ($method) {
            case 'ip':
                $key .= $request->ip();
                break;
            case 'user_agent':
                $key .= md5($request->userAgent());
                break;
            case 'ip+user_agent':
                $key .= $request->ip() . ':' . md5($request->userAgent());
                break;
            default:
                $key .= $request->ip();
        }

        return $key;
    }

    /**
     * Check if request is from bot
     */
    protected function isBot(Request $request): bool
    {
        if (!config('tracking.bot_detection.enabled', true)) {
            return false;
        }

        $userAgent = strtolower($request->userAgent() ?? '');
        $botPatterns = config('tracking.bot_detection.user_agents', []);

        foreach ($botPatterns as $pattern) {
            if (str_contains($userAgent, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check rate limit
     */
    protected function isRateLimited(Request $request): bool
    {
        if (!config('tracking.rate_limit.enabled', true)) {
            return false;
        }

        $key = config('tracking.rate_limit.key_prefix', 'click_limit:') . $request->ip();
        $maxAttempts = config('tracking.rate_limit.max_attempts', 10);
        $decaySeconds = config('tracking.rate_limit.decay_seconds', 300);

        $attempts = (int) Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            return true;
        }

        Cache::put($key, $attempts + 1, $decaySeconds);

        return false;
    }

    /**
     * Get click data from request
     */
    protected function getClickData(Request $request): array
    {
        return [
            'ip_address' => $this->getClientIp($request),
            'user_agent' => $request->userAgent(),
            'referrer' => $this->getReferrer($request),
            'user_telegram_id' => null, // Can be set if coming from Telegram
        ];
    }

    /**
     * Get client IP address
     */
    protected function getClientIp(Request $request): string
    {
        if (config('tracking.ip_detection.trust_proxies', false)) {
            $headers = config('tracking.ip_detection.headers', []);

            foreach ($headers as $header) {
                if ($request->header($header)) {
                    $ips = explode(',', $request->header($header));
                    return trim($ips[0]);
                }
            }
        }

        return $request->ip();
    }

    /**
     * Get referrer
     */
    protected function getReferrer(Request $request): ?string
    {
        if (!config('tracking.referrer.enabled', true)) {
            return null;
        }

        $referrer = $request->header('referer');

        if (!$referrer) {
            return null;
        }

        if (config('tracking.referrer.store_full_url', false)) {
            $maxLength = config('tracking.referrer.max_length', 255);
            return substr($referrer, 0, $maxLength);
        }

        // Store only domain
        $parsed = parse_url($referrer);
        return $parsed['host'] ?? null;
    }

    /**
     * Get target URL from vacancy
     * First try osonIshVacancy, then fallback to botVacancy
     */
    protected function getTargetUrl(ChannelPost $channelPost): ?string
    {
        $botVacancy = $channelPost->botVacancy;

        if (!$botVacancy) {
            return null;
        }

        // First try to get from OsonIshVacancy (preferred)
        if ($botVacancy->osonIshVacancy && $botVacancy->osonIshVacancy->show_url) {
            return $botVacancy->osonIshVacancy->show_url;
        }

        // Fallback to botVacancy show_url
        if ($botVacancy->show_url) {
            return $botVacancy->show_url;
        }

        // Last resort: construct URL from source_id if source is oson-ish
        if ($botVacancy->source === 'oson-ish' && $botVacancy->source_id) {
            return 'https://new.osonish.uz/vacancies/' . $botVacancy->source_id;
        }

        return null;
    }

    /**
     * Get statistics for vacancy
     */
    public function getVacancyStats(int $vacancyId): array
    {
        $channelPosts = ChannelPost::where('bot_vacancy_id', $vacancyId)
            ->with('channel', 'vacancyClicks')
            ->get();

        $stats = [
            'total_clicks' => 0,
            'unique_ips' => 0,
            'by_channel' => [],
        ];

        $allIps = [];

        foreach ($channelPosts as $post) {
            $clicks = $post->vacancyClicks;
            $uniqueIps = $clicks->pluck('ip_address')->unique()->filter()->count();

            $stats['by_channel'][] = [
                'channel_name' => $post->channel->name,
                'clicks' => $clicks->count(),
                'unique_ips' => $uniqueIps,
                'posted_at' => $post->posted_at,
            ];

            $stats['total_clicks'] += $clicks->count();
            $allIps = array_merge($allIps, $clicks->pluck('ip_address')->toArray());
        }

        $stats['unique_ips'] = count(array_unique(array_filter($allIps)));

        return $stats;
    }
}
