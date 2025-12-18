<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotVacancy;
use App\Models\Channel;
use App\Services\TelegramBotService;
use App\Services\VacancyPublisher;
use App\Services\ChannelAdminService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramBotService $telegram;
    protected VacancyPublisher $publisher;

    public function __construct(TelegramBotService $telegram, VacancyPublisher $publisher)
    {
        $this->telegram = $telegram;
        $this->publisher = $publisher;
    }

    /**
     * Handle Telegram webhook
     */
    public function handle(Request $request): JsonResponse
    {
        $update = $request->all();

        Log::info('Telegram webhook received', ['update_id' => $update['update_id'] ?? null]);

        // Handle callback query (button clicks)
        if (isset($update['callback_query'])) {
            return $this->handleCallbackQuery($update['callback_query']);
        }

        // Handle message
        if (isset($update['message'])) {
            return $this->handleMessage($update['message']);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Handle callback query (approve/reject buttons)
     */
    protected function handleCallbackQuery(array $callbackQuery): JsonResponse
    {
        $callbackQueryId = $callbackQuery['id'];
        $data = $callbackQuery['data'] ?? '';
        $from = $callbackQuery['from'] ?? [];

        // Parse callback data: "approve_123" or "reject_123"
        $parts = explode('_', $data);

        if (count($parts) < 2) {
            $this->telegram->answerCallbackQuery($callbackQueryId, 'Noto\'g\'ri buyruq');
            return response()->json(['ok' => true]);
        }

        $action = $parts[0];
        $vacancyId = (int) $parts[1];

        $vacancy = BotVacancy::find($vacancyId);

        if (!$vacancy) {
            $this->telegram->answerCallbackQuery($callbackQueryId, 'Vakansiya topilmadi', true);
            return response()->json(['ok' => true]);
        }

        // Check if already processed
        if (!$vacancy->isPending()) {
            $this->telegram->answerCallbackQuery(
                $callbackQueryId,
                'Bu vakansiya allaqachon qayta ishlangan',
                true
            );
            return response()->json(['ok' => true]);
        }

        // AUTHORIZATION CHECK
        $telegramUserId = $from['id'] ?? null;

        if (!$telegramUserId) {
            $this->telegram->answerCallbackQuery(
                $callbackQueryId,
                'Foydalanuvchi aniqlanmadi',
                true
            );
            return response()->json(['ok' => true]);
        }

        // Get management channel
        $managementChannel = Channel::management()->active()->first();
        if (!$managementChannel) {
            $this->telegram->answerCallbackQuery(
                $callbackQueryId,
                'Boshqaruv kanali topilmadi',
                true
            );
            return response()->json(['ok' => true]);
        }

        // Check if user is an admin for the management channel
        $adminService = app(ChannelAdminService::class);
        if (!$adminService->isAuthorizedForChannel($telegramUserId, $managementChannel->id)) {
            $this->telegram->answerCallbackQuery(
                $callbackQueryId,
                '🚫 Sizda ruxsat yo\'q. Faqat adminlar tasdiqlashi mumkin.',
                true
            );

            Log::warning('Unauthorized approval attempt', [
                'telegram_user_id' => $telegramUserId,
                'telegram_username' => $from['username'] ?? null,
                'channel_id' => $managementChannel->id,
                'action' => $action,
                'vacancy_id' => $vacancyId,
            ]);

            return response()->json(['ok' => true]);
        }
        // END AUTHORIZATION CHECK

        $userId = null; // You can implement user matching by telegram_id

        if ($action === 'approve') {
            $success = $this->publisher->handleApproval($vacancy, $userId, $telegramUserId);

            $this->telegram->answerCallbackQuery(
                $callbackQueryId,
                $success ? '✅ Tasdiqlandi va kanallarga yuborildi!' : '❌ Xatolik yuz berdi'
            );
        } elseif ($action === 'reject') {
            $success = $this->publisher->handleRejection($vacancy, $userId, $telegramUserId);

            $this->telegram->answerCallbackQuery(
                $callbackQueryId,
                $success ? '❌ Rad etildi' : '❌ Xatolik yuz berdi'
            );
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Handle regular message
     */
    protected function handleMessage(array $message): JsonResponse
    {
        // You can implement bot commands here if needed
        // For example: /start, /help, /stats

        return response()->json(['ok' => true]);
    }
}
