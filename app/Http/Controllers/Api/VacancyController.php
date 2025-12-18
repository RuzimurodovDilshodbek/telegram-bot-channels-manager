<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotVacancy;
use App\Models\OsonIshVacancy;
use App\Services\VacancyPublisher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class VacancyController extends Controller
{
    protected VacancyPublisher $publisher;

    public function __construct(VacancyPublisher $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Receive vacancy from oson-ish-api
     */
    public function store(Request $request): JsonResponse
    {
        // Verify webhook secret
        $expectedSecret = config('app.oson_ish_webhook_secret', env('OSON_ISH_WEBHOOK_SECRET'));
        $providedSecret = $request->header('X-Webhook-Secret');

        if ($expectedSecret && $providedSecret !== $expectedSecret) {
            Log::warning('Invalid webhook secret', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'vacancy_id' => 'required|integer',
            'vacancy_status' => 'required|integer',
            'title' => 'required|string|max:255',
            'count' => 'nullable|integer',
            'company_tin' => 'nullable|string|max:50',
            'company_name' => 'required|string|max:255',
            'payment_name' => 'nullable|string|max:255',
            'work_name' => 'nullable|string|max:255',
            'min_salary' => 'nullable|numeric',
            'max_salary' => 'nullable|numeric',
            'work_experience_name' => 'nullable|string|max:255',
            'age_from' => 'nullable|integer',
            'age_to' => 'nullable|integer',
            'gender' => 'nullable|integer',
            'for_whos_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'hr_fio' => 'nullable|string|max:255',
            'region_code' => 'nullable|string|max:20',
            'region_name' => 'nullable|string|max:255',
            'district_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'show_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // 1. Find existing OsonIshVacancy to check status change
        $existingOsonIshVacancy = OsonIshVacancy::where('oson_ish_vacancy_id', $data['vacancy_id'])->first();

        $previousStatus = $existingOsonIshVacancy ? $existingOsonIshVacancy->vacancy_status : null;
        $statusChanged = $existingOsonIshVacancy && $existingOsonIshVacancy->vacancy_status != $data['vacancy_status'];

        // 2. Save or update OsonIshVacancy
        $osonIshVacancy = OsonIshVacancy::updateOrCreate(
            ['oson_ish_vacancy_id' => $data['vacancy_id']],
            [
                'company_tin' => $data['company_tin'] ?? null,
                'company_name' => $data['company_name'],
                'vacancy_status' => $data['vacancy_status'],
                'title' => $data['title'],
                'count' => $data['count'] ?? 1,
                'payment_name' => $data['payment_name'] ?? null,
                'work_name' => $data['work_name'] ?? null,
                'min_salary' => $data['min_salary'] ?? null,
                'max_salary' => $data['max_salary'] ?? null,
                'work_experience_name' => $data['work_experience_name'] ?? null,
                'age_from' => $data['age_from'] ?? null,
                'age_to' => $data['age_to'] ?? null,
                'gender' => $data['gender'] ?? null,
                'for_whos_name' => $data['for_whos_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'hr_fio' => $data['hr_fio'] ?? null,
                'region_code' => $data['region_code'] ?? null,
                'region_name' => $data['region_name'] ?? null,
                'district_name' => $data['district_name'] ?? null,
                'description' => $data['description'] ?? null,
                'show_url' => $data['show_url'],
                'previous_status' => $previousStatus,
                'status_changed_at' => $statusChanged ? now() : ($existingOsonIshVacancy->status_changed_at ?? null),
            ]
        );

        Log::info('OsonIshVacancy saved', [
            'oson_ish_vacancy_id' => $osonIshVacancy->oson_ish_vacancy_id,
            'status' => $osonIshVacancy->vacancy_status,
            'status_changed' => $statusChanged,
        ]);

        // 3. Check if BotVacancy already exists
        $botVacancy = BotVacancy::where('oson_ish_vacancy_id', $data['vacancy_id'])
            ->where('source', 'oson-ish')
            ->first();

        // 4. If vacancy is active (status 2) and not published yet, create BotVacancy and send to management
        if ($osonIshVacancy->isActive() && !$botVacancy && !$statusChanged) {
            $botVacancy = BotVacancy::create([
                'oson_ish_vacancy_id' => $data['vacancy_id'],
                'source' => 'oson-ish',
                'status' => 'pending',
                'title' => $data['title'],
                'company_name' => $data['company_name'],
                'region_soato' => $data['region_code'] ?? null,
                'region_name' => $data['region_name'] ?? null,
                'district_name' => $data['district_name'] ?? null,
            ]);

            Log::info('BotVacancy created', ['bot_vacancy_id' => $botVacancy->id]);

            // Send to management channel
            $sent = $this->publisher->sendToManagement($botVacancy);

            if (!$sent) {
                Log::error('Failed to send vacancy to management channel', ['bot_vacancy_id' => $botVacancy->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Vacancy created and sent to management',
                'bot_vacancy_id' => $botVacancy->id,
                'sent_to_management' => $sent,
            ], 201);
        }

        // 5. If status changed, handle it
        if ($statusChanged && $botVacancy) {
            Log::info('Vacancy status changed', [
                'oson_ish_vacancy_id' => $osonIshVacancy->oson_ish_vacancy_id,
                'previous_status' => $osonIshVacancy->previous_status,
                'current_status' => $osonIshVacancy->vacancy_status,
            ]);

            // If became inactive, update all channel posts
            if ($osonIshVacancy->becameInactive()) {
                $this->publisher->handleVacancyInactivation($botVacancy);
            }

            // If became active again, update all channel posts
            if ($osonIshVacancy->becameActive()) {
                // If already published, update posts
                if ($botVacancy->isPublished()) {
                    $this->publisher->handleVacancyReactivation($botVacancy);
                }
                // If not published yet, send to management
                elseif ($botVacancy->isPending()) {
                    $sent = $this->publisher->sendToManagement($botVacancy);
                    if (!$sent) {
                        Log::error('Failed to send reactivated vacancy to management', ['bot_vacancy_id' => $botVacancy->id]);
                    }
                }
            }
        }

        // 6. If became active but BotVacancy doesn't exist, create and send to management
        if ($statusChanged && $osonIshVacancy->isActive() && $previousStatus != OsonIshVacancy::STATUS_ACTIVE && !$botVacancy) {
            $botVacancy = BotVacancy::create([
                'oson_ish_vacancy_id' => $data['vacancy_id'],
                'source' => 'oson-ish',
                'status' => 'pending',
                'title' => $data['title'],
                'company_name' => $data['company_name'],
                'region_soato' => $data['region_code'] ?? null,
                'region_name' => $data['region_name'] ?? null,
                'district_name' => $data['district_name'] ?? null,
            ]);

            Log::info('BotVacancy created for reactivated vacancy', ['bot_vacancy_id' => $botVacancy->id]);

            $sent = $this->publisher->sendToManagement($botVacancy);

            if (!$sent) {
                Log::error('Failed to send reactivated vacancy to management', ['bot_vacancy_id' => $botVacancy->id]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Vacancy processed successfully',
            'oson_ish_vacancy_id' => $osonIshVacancy->id,
        ]);
    }

    /**
     * Get vacancy statistics
     */
    public function statistics(Request $request, int $vacancyId): JsonResponse
    {
        $vacancy = BotVacancy::find($vacancyId);

        if (!$vacancy) {
            return response()->json(['error' => 'Vacancy not found'], 404);
        }

        $stats = [
            'vacancy_id' => $vacancy->id,
            'original_vacancy_id' => $vacancy->original_vacancy_id,
            'status' => $vacancy->status,
            'total_clicks' => $vacancy->total_clicks,
            'published_at' => $vacancy->published_at?->toISOString(),
            'channels' => [],
        ];

        foreach ($vacancy->channelPosts as $post) {
            $stats['channels'][] = [
                'channel_name' => $post->channel->name,
                'channel_type' => $post->channel->type,
                'clicks' => $post->clicks_count,
                'posted_at' => $post->posted_at->toISOString(),
            ];
        }

        return response()->json($stats);
    }
}
