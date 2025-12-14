<?php

namespace App\Services;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(config('telegram.bot_token'));
    }

    /**
     * Send message to channel
     */
    public function sendMessage(string $chatId, string $text, array $replyMarkup = null): ?array
    {
        try {
            $params = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => config('telegram.templates.vacancy.parse_mode', 'HTML'),
                'disable_web_page_preview' => false,
            ];

            if ($replyMarkup) {
                $params['reply_markup'] = json_encode($replyMarkup);
            }

            $response = $this->telegram->sendMessage($params);

            return $response->toArray();
        } catch (TelegramSDKException $e) {
            Log::error('Telegram sendMessage error: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
            return null;
        }
    }

    /**
     * Edit message text
     */
    public function editMessageText(string $chatId, int $messageId, string $text, array $replyMarkup = null): ?array
    {
        try {
            $params = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup) {
                $params['reply_markup'] = json_encode($replyMarkup);
            }

            $response = $this->telegram->editMessageText($params);

            return $response->toArray();
        } catch (TelegramSDKException $e) {
            Log::error('Telegram editMessageText error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Edit message reply markup (buttons)
     */
    public function editMessageReplyMarkup(string $chatId, int $messageId, array $replyMarkup = null): ?array
    {
        try {
            $params = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ];

            if ($replyMarkup) {
                $params['reply_markup'] = json_encode($replyMarkup);
            }

            $response = $this->telegram->editMessageReplyMarkup($params);

            return $response->toArray();
        } catch (TelegramSDKException $e) {
            Log::error('Telegram editMessageReplyMarkup error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete message
     */
    public function deleteMessage(string $chatId, int $messageId): bool
    {
        try {
            $this->telegram->deleteMessage([
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);

            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Telegram deleteMessage error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Answer callback query
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text, bool $showAlert = false): bool
    {
        try {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
                'show_alert' => $showAlert,
            ]);

            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Telegram answerCallbackQuery error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set webhook
     */
    public function setWebhook(string $url): bool
    {
        try {
            $this->telegram->setWebhook([
                'url' => $url,
                'allowed_updates' => config('telegram.webhook.allowed_updates', []),
            ]);

            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Telegram setWebhook error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): ?array
    {
        try {
            $response = $this->telegram->getWebhookInfo();
            return $response->toArray();
        } catch (TelegramSDKException $e) {
            Log::error('Telegram getWebhookInfo error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(): bool
    {
        try {
            $this->telegram->deleteWebhook();
            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Telegram deleteWebhook error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get bot info
     */
    public function getMe(): ?array
    {
        try {
            $response = $this->telegram->getMe();
            return $response->toArray();
        } catch (TelegramSDKException $e) {
            Log::error('Telegram getMe error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create inline keyboard button
     */
    public static function inlineButton(string $text, string $callbackData): array
    {
        return [
            'text' => $text,
            'callback_data' => $callbackData,
        ];
    }

    /**
     * Create URL button
     */
    public static function urlButton(string $text, string $url): array
    {
        return [
            'text' => $text,
            'url' => $url,
        ];
    }

    /**
     * Create inline keyboard
     */
    public static function inlineKeyboard(array $buttons): array
    {
        return [
            'inline_keyboard' => $buttons,
        ];
    }
}
