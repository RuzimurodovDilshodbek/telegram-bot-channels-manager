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
        // Create temporary channel post to generate tracking code (without saving)
        $channelPost = new ChannelPost([
            'bot_vacancy_id' => $vacancy->id,
            'channel_id' => $channel->id,
        ]);

        // Generate tracking code before saving
        $channelPost->unique_tracking_code = ChannelPost::generateUniqueCode();

        $message = $this->formatVacancyMessage($vacancy, $channelPost->tracking_url);

        // Send to Telegram first
        $response = $this->telegram->sendMessage(
            $channel->telegram_chat_id,
            $message
        );

        // Only save to database if Telegram succeeded
        if ($response && isset($response['message_id'])) {
            $channelPost->telegram_message_id = $response['message_id'];
            $channelPost->save();

            Log::info('Vacancy published to channel', [
                'vacancy_id' => $vacancy->id,
                'channel_id' => $channel->id,
                'message_id' => $response['message_id'],
            ]);

            return true;
        } else {
            Log::error('Failed to send vacancy to Telegram channel', [
                'vacancy_id' => $vacancy->id,
                'channel_id' => $channel->id,
                'telegram_chat_id' => $channel->telegram_chat_id,
            ]);
            return false;
        }
    }

    /**
     * Get relevant channels for vacancy
     */
    protected function getRelevantChannels(BotVacancy $vacancy): \Illuminate\Database\Eloquent\Collection
    {
        $channelIds = [];

        // Main channel - always include
        $mainChannel = Channel::main()->active()->first();
        if ($mainChannel) {
            $channelIds[] = $mainChannel->id;
        }

        // Region channels - if vacancy has region
        // Find ALL channels that serve this region (multiple channels can serve same region)
        if ($vacancy->region_soato) {
            $regionChannels = Channel::region()
                ->active()
                ->byRegion($vacancy->region_soato)
                ->get();

            foreach ($regionChannels as $regionChannel) {
                $channelIds[] = $regionChannel->id;
            }
        }

        // Return Eloquent Collection by fetching all channels by IDs
        return Channel::whereIn('id', $channelIds)->get();
    }

    /**
     * Format vacancy message for Telegram
     */
    protected function formatVacancyMessage(BotVacancy $vacancy, ?string $trackingUrl = null): string
    {
        // Get OsonIshVacancy for detailed information
        $osonIshVacancy = $vacancy->osonIshVacancy;

        if (!$osonIshVacancy) {
            // Fallback for non-oson-ish vacancies
            return $this->formatSimpleVacancyMessage($vacancy, $trackingUrl);
        }

        $url = $trackingUrl ?? $osonIshVacancy->show_url;

        $message = "📌 <b>{$osonIshVacancy->title}</b>\n\n";

        // Joylashuv
        if ($osonIshVacancy->region_name) {
            $location = $osonIshVacancy->region_name;
            if ($osonIshVacancy->district_name) {
                $location .= ", {$osonIshVacancy->district_name}";
            }
            $message .= "📍 Joylashuv: {$location}\n";
        }

        // Maosh
        $message .= "💰 Maosh: {$osonIshVacancy->formatted_salary}\n";

        // Bandlik turi (payment_name)
        if ($osonIshVacancy->payment_name) {
            $message .= "⏰ Bandlik turi: {$osonIshVacancy->payment_name}\n";
        }

        // Ish turi (work_name)
        if ($osonIshVacancy->work_name) {
            $message .= "🕐 Ish turi: {$osonIshVacancy->work_name}\n";
        }

        // Kompaniya
        if ($osonIshVacancy->company_name) {
            $message .= "🏢 Kompaniya: {$osonIshVacancy->company_name}\n";
        }

        // Xodimlar soni
        if ($osonIshVacancy->count > 1) {
            $message .= "👥 Xodimlar soni: {$osonIshVacancy->count} ta\n";
        }

        // Tajriba
        if ($osonIshVacancy->work_experience_name) {
            $message .= "🎓 Tajriba: {$osonIshVacancy->work_experience_name}\n";
        }

        // Yosh
        if ($osonIshVacancy->formatted_age) {
            $message .= "🎂 Yosh: {$osonIshVacancy->formatted_age}\n";
        }

        // Jinsi
        if ($osonIshVacancy->gender) {
            $message .= "👤 Jinsi: {$osonIshVacancy->formatted_gender}\n";
        }

        // Kim uchun
        if ($osonIshVacancy->for_whos_name) {
            $message .= "🎯 Kim uchun: {$osonIshVacancy->for_whos_name}\n";
        }

        // Ish holati - vacancy status
        $statusText = $osonIshVacancy->isActive() ? 'Faol' : 'Nofaol';
        $message .= "🔴 Ish holati: {$statusText}\n";

        // Tavsif
        if ($osonIshVacancy->description) {
            $message .= "\n📝 Talab etiladigan ko'nikmalar:\n";
            $message .= substr($osonIshVacancy->description, 0, 300);
            if (strlen($osonIshVacancy->description) > 300) {
                $message .= "...\n";
            } else {
                $message .= "\n";
            }
        }

        // Telefon va HR
        if ($osonIshVacancy->phone || $osonIshVacancy->hr_fio) {
            $message .= "\n";
            if ($osonIshVacancy->hr_fio) {
                $message .= "👨‍💼 HR: {$osonIshVacancy->hr_fio}\n";
            }
            if ($osonIshVacancy->phone) {
                $message .= "📞 Telefon: {$osonIshVacancy->phone}\n";
            }
        }

        // Batafsil ma'lumot
        $message .= "\n📝 <a href=\"{$url}\">Batafsil ma'lumot</a>\n\n";

        // Manba
        $message .= "☑️ Manba: Oson-Ish\n\n";

        // Kanal nomi
        $message .= "📣 Siz izlagan ishlar kanali";

        return $message;
    }

    /**
     * Fallback for simple vacancy message
     */
    protected function formatSimpleVacancyMessage(BotVacancy $vacancy, ?string $trackingUrl = null): string
    {
        $message = "📌 <b>{$vacancy->title}</b>\n\n";

        if ($vacancy->region_name) {
            $location = $vacancy->region_name;
            if ($vacancy->district_name) {
                $location .= ", {$vacancy->district_name}";
            }
            $message .= "📍 Joylashuv: {$location}\n";
        }

        if ($vacancy->company_name) {
            $message .= "🏢 Kompaniya: {$vacancy->company_name}\n";
        }

        $message .= "\n📝 <a href=\"{$trackingUrl}\">Batafsil ma'lumot</a>\n\n";
        $message .= "☑️ Manba: {$vacancy->source}\n\n";
        $message .= "📣 Siz izlagan ishlar kanali";

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
    public function handleApproval(BotVacancy $vacancy, ?int $userId = null, ?int $telegramUserId = null): bool
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
    public function handleRejection(BotVacancy $vacancy, ?int $userId = null, ?int $telegramUserId = null, ?string $comment = null): bool
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

    /**
     * Handle vacancy becoming inactive - update all channel posts
     */
    public function handleVacancyInactivation(BotVacancy $vacancy): bool
    {
        $channelPosts = $vacancy->channelPosts;

        if ($channelPosts->isEmpty()) {
            Log::info('No channel posts to update for inactive vacancy', ['vacancy_id' => $vacancy->id]);
            return false;
        }

        $updated = false;

        foreach ($channelPosts as $post) {
            $message = $this->formatVacancyMessage($vacancy, $post->tracking_url);

            $result = $this->telegram->editMessageText(
                $post->channel->telegram_chat_id,
                $post->telegram_message_id,
                $message
            );

            if ($result) {
                $updated = true;
                Log::info('Updated channel post for inactive vacancy', [
                    'vacancy_id' => $vacancy->id,
                    'channel_post_id' => $post->id,
                ]);
            }
        }

        return $updated;
    }

    /**
     * Handle vacancy becoming active again - update all channel posts
     */
    public function handleVacancyReactivation(BotVacancy $vacancy): bool
    {
        $channelPosts = $vacancy->channelPosts;

        if ($channelPosts->isEmpty()) {
            Log::info('No channel posts found for reactivated vacancy', ['vacancy_id' => $vacancy->id]);
            return false;
        }

        $updated = false;

        foreach ($channelPosts as $post) {
            $message = $this->formatVacancyMessage($vacancy, $post->tracking_url);

            $result = $this->telegram->editMessageText(
                $post->channel->telegram_chat_id,
                $post->telegram_message_id,
                $message
            );

            if ($result) {
                $updated = true;
                Log::info('Updated channel post for reactivated vacancy', [
                    'vacancy_id' => $vacancy->id,
                    'channel_post_id' => $post->id,
                ]);
            }
        }

        return $updated;
    }
}
