<?php

namespace App\Services;

use App\Models\BotVacancy;
use App\Models\Channel;
use App\Models\ChannelPost;
use App\Models\ActionLog;
use Illuminate\Support\Facades\Log;

class VacancyPublisher
{
    protected TelegramBotService $telegram;

    public function __construct(TelegramBotService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Send vacancy to management channel for approval
     */
    public function sendToManagement(BotVacancy $vacancy): bool
    {
        $managementChannel = Channel::management()->active()->first();

        if (!$managementChannel) {
            Log::error('Management channel not found');
            return false;
        }

        $message = $this->formatVacancyMessage($vacancy);
        $keyboard = $this->getApprovalKeyboard($vacancy->id);

        $response = $this->telegram->sendMessage(
            $managementChannel->telegram_chat_id,
            $message,
            $keyboard
        );

        if ($response && isset($response['message_id'])) {
            $vacancy->update([
                'management_message_id' => $response['message_id'],
                'status' => 'pending',
            ]);

            return true;
        }

        return false;
    }

    /**
     * Publish vacancy to all relevant channels
     */
    public function publishToChannels(BotVacancy $vacancy): bool
    {
        if (!$vacancy->isApproved()) {
            Log::warning('Cannot publish vacancy - not approved', ['vacancy_id' => $vacancy->id]);
            return false;
        }

        $channels = $this->getRelevantChannels($vacancy);

        if ($channels->isEmpty()) {
            Log::warning('No relevant channels found for vacancy', ['vacancy_id' => $vacancy->id]);
            return false;
        }

        $published = false;

        foreach ($channels as $channel) {
            if ($this->publishToChannel($vacancy, $channel)) {
                $published = true;
                $channel->incrementPostsCount();
            }
        }

        if ($published) {
            $vacancy->markAsPublished();
        }

        return $published;
    }

    /**
     * Publish vacancy to a single channel
     */
    protected function publishToChannel(BotVacancy $vacancy, Channel $channel): bool
    {
        // Create channel post first to generate tracking code
        $channelPost = new ChannelPost([
            'bot_vacancy_id' => $vacancy->id,
            'channel_id' => $channel->id,
        ]);
        $channelPost->save();

        $message = $this->formatVacancyMessage($vacancy, $channelPost->tracking_url);

        $response = $this->telegram->sendMessage(
            $channel->telegram_chat_id,
            $message
        );

        if ($response && isset($response['message_id'])) {
            $channelPost->update([
                'telegram_message_id' => $response['message_id'],
            ]);

            Log::info('Vacancy published to channel', [
                'vacancy_id' => $vacancy->id,
                'channel_id' => $channel->id,
                'message_id' => $response['message_id'],
            ]);

            return true;
        } else {
            // Delete channel post if sending failed
            $channelPost->delete();
            return false;
        }
    }

    /**
     * Get relevant channels for vacancy
     */
    protected function getRelevantChannels(BotVacancy $vacancy): \Illuminate\Database\Eloquent\Collection
    {
        $channels = collect();

        // Main channel - always include
        $mainChannel = Channel::main()->active()->first();
        if ($mainChannel) {
            $channels->push($mainChannel);
        }

        // Region channels - if vacancy has region
        // Find ALL channels that serve this region (multiple channels can serve same region)
        if ($vacancy->region_soato) {
            $regionChannels = Channel::region()
                ->active()
                ->byRegion($vacancy->region_soato)
                ->get();

            foreach ($regionChannels as $regionChannel) {
                $channels->push($regionChannel);
            }
        }

        return $channels;
    }

    /**
     * Format vacancy message for Telegram
     */
    protected function formatVacancyMessage(BotVacancy $vacancy, ?string $trackingUrl = null): string
    {
        $url = $trackingUrl ?? $vacancy->show_url;

        $message = "🏢 <b>{$vacancy->title}</b>\n\n";

        if ($vacancy->company_name) {
            $message .= "🏭 Kompaniya: {$vacancy->company_name}\n";
        }

        if ($vacancy->region_name) {
            $location = $vacancy->region_name;
            if ($vacancy->district_name) {
                $location .= ", {$vacancy->district_name}";
            }
            $message .= "📍 Joylashuv: {$location}\n";
        }

        $message .= "💰 Maosh: {$vacancy->formatted_salary}\n";

        if ($vacancy->work_type) {
            $message .= "🕐 Ish turi: {$vacancy->work_type}\n";
        }

        if ($vacancy->busyness_type) {
            $message .= "⏰ Bandlik: {$vacancy->busyness_type}\n";
        }

        $message .= "\n";

        if ($url) {
            $message .= "📝 <a href=\"{$url}\">Batafsil ma'lumot</a>\n\n";
        }

        $message .= "📌 Manba: {$vacancy->source}";

        return $message;
    }

    /**
     * Get approval keyboard for management channel
     */
    protected function getApprovalKeyboard(int $vacancyId): array
    {
        return TelegramBotService::inlineKeyboard([
            [
                TelegramBotService::inlineButton(
                    config('telegram.templates.buttons.approve', '✅ Tasdiqlash'),
                    "approve_{$vacancyId}"
                ),
                TelegramBotService::inlineButton(
                    config('telegram.templates.buttons.reject', '❌ Rad etish'),
                    "reject_{$vacancyId}"
                ),
            ],
        ]);
    }

    /**
     * Handle approval from management channel
     */
    public function handleApproval(BotVacancy $vacancy, int $userId, ?int $telegramUserId = null): bool
    {
        $vacancy->approve();

        // Log action
        ActionLog::create([
            'bot_vacancy_id' => $vacancy->id,
            'user_id' => $userId,
            'telegram_user_id' => $telegramUserId,
            'action' => 'approved',
            'action_at' => now(),
        ]);

        // Update management message - remove buttons
        $this->updateManagementMessage($vacancy, '✅ Tasdiqlandi');

        // Publish to channels
        return $this->publishToChannels($vacancy);
    }

    /**
     * Handle rejection from management channel
     */
    public function handleRejection(BotVacancy $vacancy, int $userId, ?int $telegramUserId = null, ?string $comment = null): bool
    {
        $vacancy->reject();

        // Log action
        ActionLog::create([
            'bot_vacancy_id' => $vacancy->id,
            'user_id' => $userId,
            'telegram_user_id' => $telegramUserId,
            'action' => 'rejected',
            'comment' => $comment,
            'action_at' => now(),
        ]);

        // Update management message - remove buttons
        $this->updateManagementMessage($vacancy, '❌ Rad etildi');

        return true;
    }

    /**
     * Update management message
     */
    protected function updateManagementMessage(BotVacancy $vacancy, string $statusText): void
    {
        if (!$vacancy->management_message_id) {
            return;
        }

        $managementChannel = Channel::management()->active()->first();

        if (!$managementChannel) {
            return;
        }

        $message = $this->formatVacancyMessage($vacancy) . "\n\n<i>{$statusText}</i>";

        $this->telegram->editMessageText(
            $managementChannel->telegram_chat_id,
            $vacancy->management_message_id,
            $message,
            ['inline_keyboard' => []] // Empty keyboard
        );
    }
}
