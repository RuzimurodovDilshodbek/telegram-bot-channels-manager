<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PollBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PollBotWebhookController extends Controller
{
    protected PollBotService $pollBotService;

    public function __construct(PollBotService $pollBotService)
    {
        $this->pollBotService = $pollBotService;
    }

    /**
     * Handle incoming webhook from Telegram
     */
    public function handle(Request $request)
    {
        try {
            $update = $request->all();

            Log::info('PollBot webhook received', ['update' => $update]);

            // Handle the update
            $this->pollBotService->handleUpdate($update);

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('PollBot webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get webhook info (for debugging)
     */
    public function info()
    {
        try {
            $telegram = new \Telegram\Bot\Api(config('telegram.poll_bot_token'));
            $webhookInfo = $telegram->getWebhookInfo();

            return response()->json([
                'ok' => true,
                'webhook_info' => $webhookInfo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set webhook
     */
    public function setWebhook()
    {
        try {
            $telegram = new \Telegram\Bot\Api(config('telegram.poll_bot_token'));
            $webhookUrl = config('telegram.poll_webhook_url');

            $response = $telegram->setWebhook([
                'url' => $webhookUrl,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Webhook set successfully',
                'webhook_url' => $webhookUrl,
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove webhook
     */
    public function removeWebhook()
    {
        try {
            $telegram = new \Telegram\Bot\Api(config('telegram.poll_bot_token'));
            $response = $telegram->removeWebhook();

            return response()->json([
                'ok' => true,
                'message' => 'Webhook removed successfully',
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
