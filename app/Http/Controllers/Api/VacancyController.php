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
            'source' => 'required|string|max:50',
            'source_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'company_tin' => 'nullable|string|max:50',
            'filial_name' => 'nullable|string|max:255',
            'region_soato' => 'nullable|string|max:20',
            'district_soato' => 'nullable|string|max:20',
            'region_name' => 'nullable|string|max:255',
            'district_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'position_count' => 'nullable|integer',
            'min_salary' => 'nullable|integer',
            'max_salary' => 'nullable|integer',
            'payment_type' => 'nullable|integer',
            'work_type' => 'nullable|integer',
            'busyness_type' => 'nullable|integer',
            'working_time_from' => 'nullable|string',
            'working_time_to' => 'nullable|string',
            'min_education' => 'nullable|integer',
            'work_experience' => 'nullable|integer',
            'age_from' => 'nullable|integer',
            'age_to' => 'nullable|integer',
            'gender' => 'nullable|integer',
            'languages' => 'nullable|array',
            'skills' => 'nullable|array',
            'driver_licenses' => 'nullable|array',
            'info' => 'nullable|string',
            'benefit_ids' => 'nullable|array',
            'test_period_id' => 'nullable|integer',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'additional_phone' => 'nullable|string|max:50',
            'another_network' => 'nullable|string|max:255',
            'is_hidden_network' => 'nullable|integer',
            'mmk_position_id' => 'nullable|integer',
            'mmk_position_name' => 'nullable|string|max:255',
            'mmk_group_id' => 'nullable|integer',
            'mmk_group_name' => 'nullable|string|max:255',
            'show_url' => 'required|url',
            'created_at' => 'nullable|string',
            'approved_at' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Prepare description from info field
        $description = $data['info'] ?? null;

        // Prepare work_type and busyness_type as string for display
        $workType = isset($data['work_type']) ? (string) $data['work_type'] : null;
        $busynessType = isset($data['busyness_type']) ? (string) $data['busyness_type'] : null;

        // Check if vacancy already exists
        $existingVacancy = BotVacancy::where('original_vacancy_id', $data['source_id'])
            ->where('source', $data['source'])
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
                'salary_min' => $data['min_salary'] ?? null,
                'salary_max' => $data['max_salary'] ?? null,
                'work_type' => $workType,
                'busyness_type' => $busynessType,
                'description' => $description,
                'show_url' => $data['show_url'],
                'raw_data' => $data,
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
            'original_vacancy_id' => $data['source_id'],
            'source' => $data['source'],
            'status' => 'pending',
            'title' => $data['title'],
            'company_name' => $data['company_name'] ?? null,
            'region_soato' => $data['region_soato'] ?? null,
            'region_name' => $data['region_name'] ?? null,
            'district_soato' => $data['district_soato'] ?? null,
            'district_name' => $data['district_name'] ?? null,
            'salary_min' => $data['min_salary'] ?? null,
            'salary_max' => $data['max_salary'] ?? null,
            'work_type' => $workType,
            'busyness_type' => $busynessType,
            'description' => $description,
            'show_url' => $data['show_url'],
            'raw_data' => $data,
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
