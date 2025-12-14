<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotVacancy;
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
            'title' => 'required|string|max:255',
            'source' => 'sometimes|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'region_soato' => 'nullable|string|max:10',
            'region_name' => 'nullable|string|max:255',
            'district_soato' => 'nullable|string|max:10',
            'district_name' => 'nullable|string|max:255',
            'salary_min' => 'nullable|integer',
            'salary_max' => 'nullable|integer',
            'work_type' => 'nullable|string|max:100',
            'busyness_type' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'show_url' => 'required|url',
            'raw_data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Check if vacancy already exists
        $existingVacancy = BotVacancy::where('original_vacancy_id', $data['vacancy_id'])
            ->where('source', $data['source'] ?? 'oson-ish')
            ->first();

        if ($existingVacancy) {
            // Update existing vacancy
            $existingVacancy->update([
                'title' => $data['title'],
                'company_name' => $data['company_name'] ?? null,
                'region_soato' => $data['region_soato'] ?? null,
                'region_name' => $data['region_name'] ?? null,
                'district_soato' => $data['district_soato'] ?? null,
                'district_name' => $data['district_name'] ?? null,
                'salary_min' => $data['salary_min'] ?? null,
                'salary_max' => $data['salary_max'] ?? null,
                'work_type' => $data['work_type'] ?? null,
                'busyness_type' => $data['busyness_type'] ?? null,
                'description' => $data['description'] ?? null,
                'show_url' => $data['show_url'],
                'raw_data' => $data['raw_data'] ?? null,
            ]);

            Log::info('Vacancy updated', ['vacancy_id' => $existingVacancy->id]);

            return response()->json([
                'success' => true,
                'message' => 'Vacancy updated',
                'vacancy_id' => $existingVacancy->id,
            ]);
        }

        // Create new vacancy
        $vacancy = BotVacancy::create([
            'original_vacancy_id' => $data['vacancy_id'],
            'source' => $data['source'] ?? 'oson-ish',
            'status' => 'pending',
            'title' => $data['title'],
            'company_name' => $data['company_name'] ?? null,
            'region_soato' => $data['region_soato'] ?? null,
            'region_name' => $data['region_name'] ?? null,
            'district_soato' => $data['district_soato'] ?? null,
            'district_name' => $data['district_name'] ?? null,
            'salary_min' => $data['salary_min'] ?? null,
            'salary_max' => $data['salary_max'] ?? null,
            'work_type' => $data['work_type'] ?? null,
            'busyness_type' => $data['busyness_type'] ?? null,
            'description' => $data['description'] ?? null,
            'show_url' => $data['show_url'],
            'raw_data' => $data['raw_data'] ?? null,
        ]);

        Log::info('Vacancy created', ['vacancy_id' => $vacancy->id]);

        // Send to management channel
        $sent = $this->publisher->sendToManagement($vacancy);

        if (!$sent) {
            Log::error('Failed to send vacancy to management channel', ['vacancy_id' => $vacancy->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vacancy created and sent to management',
            'vacancy_id' => $vacancy->id,
            'sent_to_management' => $sent,
        ], 201);
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
