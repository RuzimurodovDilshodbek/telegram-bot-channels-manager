<?php

namespace App\Services;

use App\Models\Poll;
use App\Models\PollCandidate;
use App\Models\PollParticipant;
use App\Models\PollVote;
use App\Models\PollChannelPost;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PollBotService
{
    protected Api $telegram;
    protected const BOT_SOURCE = 'poll_bot';

    public function __construct()
    {
        $this->telegram = new Api(config('telegram.poll_bot_token'));
    }

    /**
     * Handle incoming updates from webhook
     */
    public function handleUpdate(array $update): void
    {
        try {
            // Message handling
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }

            // Callback query handling (button clicks)
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }
        } catch (\Exception $e) {
            Log::error('PollBot handleUpdate error: ' . $e->getMessage(), [
                'update' => $update,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle incoming messages
     */
    protected function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $from = $message['from'];

        // Handle /start command with poll parameter
        if (isset($message['text']) && str_starts_with($message['text'], '/start')) {
            $this->handleStart($chatId, $from, $message['text']);
            return;
        }

        // Handle phone number contact
        if (isset($message['contact'])) {
            $this->handlePhoneNumber($chatId, $from, $message['contact']);
            return;
        }

        // Default response
        $this->sendMessage($chatId, "Iltimos, so'rovnoma linkini bosib kirish orqali ishtirok eting.");
    }

    /**
     * Handle /start command
     */
    protected function handleStart(string $chatId, array $from, string $text): void
    {
        // Parse poll ID and optional candidate ID from /start poll_123 or /start poll_123_vote_456
        preg_match('/\/start poll_(\d+)(?:_vote_(\d+))?/', $text, $matches);

        if (!isset($matches[1])) {
            $this->sendMessage($chatId, "Xush kelibsiz! Iltimos, so'rovnoma linkini bosib kirish orqali ishtirok eting.");
            return;
        }

        $pollId = $matches[1];
        $preselectedCandidateId = $matches[2] ?? null;

        $poll = Poll::with('candidates')->find($pollId);

        if (!$poll) {
            $this->sendMessage($chatId, "So'rovnoma topilmadi.");
            return;
        }

        if (!$poll->isActive()) {
            $this->sendMessage($chatId, "Bu so'rovnoma tugagan yoki faol emas.");
            return;
        }

        // Create or update participant
        $participant = PollParticipant::updateOrCreate(
            [
                'poll_id' => $poll->id,
                'chat_id' => $chatId,
            ],
            [
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'username' => $from['username'] ?? null,
                'bot_source' => self::BOT_SOURCE,
            ]
        );

        // Check if already voted
        if ($participant->hasVoted()) {
            $this->sendMessage($chatId, "Siz allaqachon ovoz bergansiz. Rahmat!");
            $this->showCurrentResults($chatId, $poll);
            return;
        }

        // Request phone number if required
        if ($poll->require_phone && !$participant->phone_verified) {
            $this->requestPhoneNumber($chatId, $poll);
            return;
        }

        // Check subscription if required
        if ($poll->require_subscription && !$participant->subscription_verified) {
            $this->checkSubscription($chatId, $poll, $participant);
            return;
        }

        // If candidate was preselected, show confirmation
        if ($preselectedCandidateId) {
            $candidate = PollCandidate::find($preselectedCandidateId);
            if ($candidate && $candidate->poll_id == $poll->id) {
                $this->showVoteConfirmation($chatId, $poll, $candidate);
                return;
            }
        }

        // Show candidates
        $this->showCandidates($chatId, $poll);
    }

    /**
     * Request phone number from user
     */
    protected function requestPhoneNumber(string $chatId, Poll $poll): void
    {
        $keyboard = [
            'keyboard' => [
                [
                    [
                        'text' => '📱 Telefon raqamni yuborish',
                        'request_contact' => true
                    ]
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];

        $message = "📱 <b>{$poll->title}</b>\n\n";
        $message .= "Ovoz berish uchun telefon raqamingizni yuboring.";

        $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle phone number submission
     */
    protected function handlePhoneNumber(string $chatId, array $from, array $contact): void
    {
        // Find active participant
        $participant = PollParticipant::where('chat_id', $chatId)
            ->whereHas('poll', function ($query) {
                $query->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
            })
            ->whereNull('verified_at')
            ->first();

        if (!$participant) {
            $this->sendMessage($chatId, "Iltimos, avval so'rovnoma linkidan kiring.");
            return;
        }

        // Update participant with phone
        $participant->update([
            'phone' => $contact['phone_number'],
            'phone_verified' => true,
        ]);

        $poll = $participant->poll;

        // Check subscription next
        if ($poll->require_subscription && !$participant->subscription_verified) {
            $this->checkSubscription($chatId, $poll, $participant);
            return;
        }

        // Show candidates
        $this->showCandidates($chatId, $poll);
    }

    /**
     * Check channel subscription
     */
    protected function checkSubscription(string $chatId, Poll $poll, PollParticipant $participant): void
    {
        if (empty($poll->required_channels)) {
            $participant->update(['subscription_verified' => true]);
            $this->showCandidates($chatId, $poll);
            return;
        }

        $notSubscribed = [];

        foreach ($poll->required_channels as $channelId) {
            try {
                Log::info('Checking subscription', [
                    'user_chat_id' => $chatId,
                    'channel_id' => $channelId,
                ]);

                $response = $this->telegram->getChatMember([
                    'chat_id' => $channelId,
                    'user_id' => $chatId,
                ]);

                // Get status from response array
                $status = $response['status'] ?? ($response->get('status') ?? 'left');

                Log::info('Subscription status', [
                    'channel_id' => $channelId,
                    'status' => $status,
                    'full_response' => $response,
                ]);

                if (!in_array($status, ['member', 'administrator', 'creator'])) {
                    $notSubscribed[] = $channelId;
                }
            } catch (\Exception $e) {
                Log::error('Check subscription error', [
                    'channel_id' => $channelId,
                    'user_chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
                // Agar kanal topilmasa yoki xato bo'lsa, obuna bo'lmagan deb hisoblaymiz
                $notSubscribed[] = $channelId;
            }
        }

        if (empty($notSubscribed)) {
            $participant->update(['subscription_verified' => true]);
            $this->showCandidates($chatId, $poll);
        } else {
            $this->showSubscriptionRequired($chatId, $poll, $notSubscribed);
        }
    }

    /**
     * Show subscription required message
     */
    protected function showSubscriptionRequired(string $chatId, Poll $poll, array $notSubscribedChannels): void
    {
        $message = "📢 <b>Kanallarimizga obuna bo'ling!</b>\n\n";
        $message .= "Ovoz berish uchun quyidagi kanallarga obuna bo'lishingiz kerak:\n\n";

        $buttons = [];
        $channelCount = 0;

        // Show all required channels, not just the ones user is not subscribed to
        foreach ($poll->required_channels as $channelId) {
            try {
                $chat = $this->telegram->getChat(['chat_id' => $channelId]);
                $channelName = $chat->getTitle();
                $channelUsername = $chat->getUsername();

                $channelCount++;

                // Add to message text
                if ($channelUsername) {
                    $isSubscribed = !in_array($channelId, $notSubscribedChannels);
                    $statusEmoji = $isSubscribed ? '✅' : '❌';
                    $message .= "{$statusEmoji} <b>{$channelName}</b> (@{$channelUsername})\n";

                    // Add button
                    $buttons[] = [
                        [
                            'text' => "📢 {$channelName}",
                            'url' => "https://t.me/{$channelUsername}"
                        ]
                    ];
                } else {
                    $message .= "❌ <b>{$channelName}</b>\n";
                }
            } catch (\Exception $e) {
                Log::error('Get channel info error', [
                    'channel_id' => $channelId,
                    'error' => $e->getMessage()
                ]);
                $message .= "❌ Kanal (ID: {$channelId})\n";
            }
        }

        if ($channelCount > 0) {
            $message .= "\nBarcha kanallarga obuna bo'lgandan keyin \"✅ Obunani tekshirish\" tugmasini bosing.";
        }

        // Add check subscription button
        $buttons[] = [
            [
                'text' => '✅ Obunani tekshirish',
                'callback_data' => "check_sub_{$poll->id}"
            ]
        ];

        $keyboard = ['inline_keyboard' => $buttons];

        $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Show poll candidates
     */
    protected function showCandidates(string $chatId, Poll $poll): void
    {
        $message = "🗳 <b>{$poll->title}</b>\n\n";

        if ($poll->description) {
            $message .= "{$poll->description}\n\n";
        }

        $message .= "Nomzodlardan birini tanlang:\n\n";

        $buttons = [];
        foreach ($poll->candidates()->where('is_active', true)->get() as $candidate) {
            $buttons[] = [
                [
                    'text' => $candidate->name,
                    'callback_data' => "vote_{$poll->id}_{$candidate->id}"
                ]
            ];
        }

        $keyboard = ['inline_keyboard' => $buttons];

        if ($poll->image && Storage::exists($poll->image)) {
            $this->sendPhoto($chatId, Storage::path($poll->image), $message, $keyboard);
        } else {
            $this->sendMessage($chatId, $message, $keyboard);
        }
    }

    /**
     * Handle callback queries (button clicks)
     */
    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $data = $callbackQuery['data'];
        $from = $callbackQuery['from'];

        // Answer callback to remove loading state
        $this->telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery['id']]);

        // Check subscription
        if (str_starts_with($data, 'check_sub_')) {
            $pollId = str_replace('check_sub_', '', $data);
            $poll = Poll::find($pollId);
            $participant = PollParticipant::where('poll_id', $pollId)->where('chat_id', $chatId)->first();

            if ($poll && $participant) {
                $this->checkSubscription($chatId, $poll, $participant);
            }
            return;
        }

        // Vote
        if (str_starts_with($data, 'vote_')) {
            $parts = explode('_', $data);
            $pollId = $parts[1];
            $candidateId = $parts[2];

            $this->handleVote($chatId, $from, $pollId, $candidateId, $messageId);
            return;
        }

        // Confirm vote
        if (str_starts_with($data, 'confirm_vote_')) {
            $parts = explode('_', $data);
            $pollId = $parts[2];
            $candidateId = $parts[3];

            $this->confirmVote($chatId, $from, $pollId, $candidateId, $messageId);
            return;
        }

        // Cancel vote
        if (str_starts_with($data, 'cancel_vote_')) {
            $pollId = str_replace('cancel_vote_', '', $data);
            $poll = Poll::find($pollId);

            if ($poll) {
                $this->showCandidates($chatId, $poll);
            }
            return;
        }

        // ReCaptcha verification
        if (str_starts_with($data, 'captcha_')) {
            $parts = explode('_', $data);
            $pollId = $parts[1];
            $candidateId = $parts[2];
            $userAnswer = $parts[3];
            $correctAnswer = $parts[4];

            if ($userAnswer == $correctAnswer) {
                // Mark as verified
                $participant = PollParticipant::where('poll_id', $pollId)
                    ->where('chat_id', $chatId)
                    ->first();

                if ($participant) {
                    $participant->update(['recaptcha_verified' => true]);

                    // Continue with voting
                    $this->handleVote($chatId, $from, $pollId, $candidateId, $messageId);
                }
            } else {
                // Wrong answer
                $this->sendMessage($chatId, "❌ Noto'g'ri javob! Iltimos, qaytadan urinib ko'ring.");
                $poll = Poll::find($pollId);
                $candidate = PollCandidate::find($candidateId);
                if ($poll && $candidate) {
                    $this->showRecaptcha($chatId, $poll, $candidate);
                }
            }
            return;
        }
    }

    /**
     * Handle vote selection
     */
    protected function handleVote(string $chatId, array $from, int $pollId, int $candidateId, int $messageId): void
    {
        $poll = Poll::find($pollId);
        $candidate = PollCandidate::find($candidateId);

        if (!$poll || !$candidate || !$poll->isActive()) {
            $this->sendMessage($chatId, "Xatolik yuz berdi. Iltimos, qaytadan urinib ko'ring.");
            return;
        }

        $participant = PollParticipant::where('poll_id', $pollId)->where('chat_id', $chatId)->first();

        if (!$participant || $participant->hasVoted()) {
            $this->sendMessage($chatId, "Siz allaqachon ovoz bergansiz!");
            return;
        }

        // Show confirmation
        $message = "✅ <b>Ovozingizni tasdiqlang</b>\n\n";
        $message .= "Siz <b>{$candidate->name}</b> nomzodiga ovoz berasiz.\n\n";
        $message .= "Tasdiqlaysizmi?";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Ha, tasdiqlash', 'callback_data' => "confirm_vote_{$pollId}_{$candidateId}"],
                    ['text' => '❌ Bekor qilish', 'callback_data' => "cancel_vote_{$pollId}"]
                ]
            ]
        ];

        $this->editMessageText($chatId, $messageId, $message, $keyboard);
    }

    /**
     * Confirm and record vote
     */
    protected function confirmVote(string $chatId, array $from, int $pollId, int $candidateId, int $messageId): void
    {
        $poll = Poll::find($pollId);
        $candidate = PollCandidate::find($candidateId);
        $participant = PollParticipant::where('poll_id', $pollId)->where('chat_id', $chatId)->first();

        if (!$poll || !$candidate || !$participant || $participant->hasVoted()) {
            $this->sendMessage($chatId, "Xatolik yuz berdi.");
            return;
        }

        // Check if participant is fully verified
        if (!$participant->isFullyVerified()) {
            // Check what's missing
            if ($poll->require_phone && !$participant->phone_verified) {
                $this->requestPhoneNumber($chatId, $poll);
                return;
            }

            if ($poll->require_subscription && !$participant->subscription_verified) {
                $this->checkSubscription($chatId, $poll, $participant);
                return;
            }

            if ($poll->enable_recaptcha && !$participant->recaptcha_verified) {
                $this->showRecaptcha($chatId, $poll, $candidate);
                return;
            }
        }

        // Record vote
        PollVote::create([
            'poll_id' => $poll->id,
            'poll_candidate_id' => $candidate->id,
            'poll_participant_id' => $participant->id,
            'chat_id' => $chatId,
            'ip_address' => request()->ip() ?? null,
            'user_agent' => request()->userAgent() ?? null,
            'voted_at' => now(),
        ]);

        // Update counters
        $candidate->increment('vote_count');
        $poll->increment('total_votes');

        // Mark participant as verified
        $participant->update([
            'verified_at' => now(),
            'recaptcha_verified' => true, // Auto-verify if we reached this point
        ]);

        // Update channel posts
        $this->updateChannelPosts($poll);

        // Send success message
        $message = "🎉 <b>Rahmat!</b>\n\n";
        $message .= "Sizning ovozingiz <b>{$candidate->name}</b> nomzodiga qabul qilindi.\n\n";
        $message .= "Hozirgi natijalar:\n";
        $message .= "Jami ovozlar: <b>{$poll->total_votes}</b>";

        $this->editMessageText($chatId, $messageId, $message);
    }

    /**
     * Show ReCaptcha verification (simple math problem)
     */
    protected function showRecaptcha(string $chatId, Poll $poll, PollCandidate $candidate): void
    {
        // Generate a simple math problem
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $correctAnswer = $num1 + $num2;

        // Store the correct answer in participant data (we'll use a simple approach)
        $participant = PollParticipant::where('poll_id', $poll->id)
            ->where('chat_id', $chatId)
            ->first();

        if ($participant) {
            // We'll verify using callback data
            $message = "🤖 <b>Inson ekanligingizni tasdiqlang</b>\n\n";
            $message .= "Quyidagi hisoblash natijasini tanlang:\n\n";
            $message .= "<b>{$num1} + {$num2} = ?</b>";

            // Generate options (correct answer and 3 wrong ones)
            $options = [
                $correctAnswer,
                $correctAnswer + rand(1, 3),
                $correctAnswer - rand(1, 3),
                $correctAnswer + rand(4, 6),
            ];

            // Shuffle options
            shuffle($options);

            $buttons = [];
            foreach ($options as $option) {
                $buttons[] = [
                    [
                        'text' => (string)$option,
                        'callback_data' => "captcha_{$poll->id}_{$candidate->id}_{$option}_{$correctAnswer}"
                    ]
                ];
            }

            $keyboard = ['inline_keyboard' => $buttons];

            $this->sendMessage($chatId, $message, $keyboard);
        }
    }

    /**
     * Publish poll to channels
     */
    public function publishToChannels(Poll $poll, array $channelIds): void
    {
        foreach ($channelIds as $channelId) {
            try {
                $message = $this->generatePollMessage($poll);
                $keyboard = $this->generatePollKeyboard($poll);

                // Send with photo if exists
                if ($poll->image && Storage::exists($poll->image)) {
                    $response = $this->sendPhoto($channelId, Storage::path($poll->image), $message, $keyboard);
                } else {
                    $response = $this->sendMessage($channelId, $message, $keyboard);
                }

                if ($response) {
                    PollChannelPost::create([
                        'poll_id' => $poll->id,
                        'channel_id' => $channelId,
                        'message_id' => $response['message_id'],
                        'post_text' => $message,
                        'posted_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Publish poll to channel error: ' . $e->getMessage(), [
                    'poll_id' => $poll->id,
                    'channel_id' => $channelId,
                ]);
            }
        }
    }

    /**
     * Update channel posts with new vote counts
     */
    public function updateChannelPosts(Poll $poll): void
    {
        $posts = $poll->channelPosts;

        foreach ($posts as $post) {
            try {
                $message = $this->generatePollMessage($poll);
                $keyboard = $this->generatePollKeyboard($poll);

                // Update only the caption and keyboard (can't change photo)
                if ($poll->image && Storage::exists($poll->image)) {
                    $this->editMessageCaption($post->channel_id, (int)$post->message_id, $message, $keyboard);
                } else {
                    $this->editMessageText($post->channel_id, (int)$post->message_id, $message, $keyboard);
                }

                $post->update([
                    'last_updated_at' => now(),
                    'update_count' => $post->update_count + 1,
                ]);
            } catch (\Exception $e) {
                Log::error('Update channel post error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Generate poll message text
     */
    protected function generatePollMessage(Poll $poll): string
    {
        $message = "🗳 <b>{$poll->title}</b>\n\n";

        if ($poll->description) {
            $message .= "{$poll->description}\n\n";
        }

        $message .= "📊 <b>Nomzodlar:</b>\n";
        $message .= "Ovoz berish uchun pastdagi tugmalarni bosing!\n\n";

        $message .= "📈 Jami ovozlar: <b>{$poll->total_votes}</b>\n";
        $message .= "⏰ Tugash sanasi: {$poll->end_date->format('d.m.Y H:i')}\n";

        return $message;
    }

    /**
     * Generate poll keyboard
     */
    protected function generatePollKeyboard(Poll $poll): array
    {
        $buttons = [];

        // Add candidate buttons with vote counts
        foreach ($poll->candidates()->where('is_active', true)->orderBy('order')->get() as $candidate) {
            $percentage = $poll->total_votes > 0 ? round(($candidate->vote_count / $poll->total_votes) * 100, 1) : 0;
            $buttonText = "{$candidate->name} - {$candidate->vote_count} ovoz ({$percentage}%)";

            $buttons[] = [
                [
                    'text' => $buttonText,
                    'url' => "https://t.me/" . config('telegram.poll_bot_username') . "?start=poll_{$poll->id}_vote_{$candidate->id}"
                ]
            ];
        }

        return [
            'inline_keyboard' => $buttons
        ];
    }

    /**
     * Send message
     */
    protected function sendMessage(string $chatId, string $text, ?array $replyMarkup = null): ?array
    {
        try {
            $params = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup) {
                $params['reply_markup'] = json_encode($replyMarkup);
            }

            return $this->telegram->sendMessage($params)->toArray();
        } catch (TelegramSDKException $e) {
            Log::error('PollBot sendMessage error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send photo
     */
    protected function sendPhoto(string $chatId, string $photo, string $caption, ?array $replyMarkup = null): ?array
    {
        try {
            $params = [
                'chat_id' => $chatId,
                'photo' => fopen($photo, 'r'),
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup) {
                $params['reply_markup'] = json_encode($replyMarkup);
            }

            return $this->telegram->sendPhoto($params)->toArray();
        } catch (TelegramSDKException $e) {
            Log::error('PollBot sendPhoto error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Edit message text
     */
    protected function editMessageText(string $chatId, int $messageId, string $text, ?array $replyMarkup = null): ?array
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

            return $this->telegram->editMessageText($params)->toArray();
        } catch (TelegramSDKException $e) {
            Log::error('PollBot editMessageText error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Edit message caption (for photos)
     */
    protected function editMessageCaption(string $chatId, int $messageId, string $caption, ?array $replyMarkup = null): ?array
    {
        try {
            $params = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup) {
                $params['reply_markup'] = json_encode($replyMarkup);
            }

            return $this->telegram->editMessageCaption($params)->toArray();
        } catch (TelegramSDKException $e) {
            Log::error('PollBot editMessageCaption error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Show vote confirmation directly
     */
    protected function showVoteConfirmation(string $chatId, Poll $poll, PollCandidate $candidate): void
    {
        $message = "✅ <b>Ovozingizni tasdiqlang</b>\n\n";
        $message .= "Siz <b>{$candidate->name}</b> nomzodiga ovoz berasiz.\n\n";
        $message .= "Tasdiqlaysizmi?";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Ha, tasdiqlash', 'callback_data' => "confirm_vote_{$poll->id}_{$candidate->id}"],
                    ['text' => '❌ Bekor qilish', 'callback_data' => "cancel_vote_{$poll->id}"]
                ]
            ]
        ];

        $this->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Show current poll results
     */
    protected function showCurrentResults(string $chatId, Poll $poll): void
    {
        $message = "📊 <b>Hozirgi natijalar:</b>\n\n";

        foreach ($poll->candidates()->orderByDesc('vote_count')->get() as $candidate) {
            $percentage = $poll->total_votes > 0 ? round(($candidate->vote_count / $poll->total_votes) * 100, 1) : 0;
            $message .= "• <b>{$candidate->name}</b>\n";
            $message .= "  {$candidate->vote_count} ovoz ({$percentage}%)\n\n";
        }

        $message .= "\n📈 Jami ovozlar: <b>{$poll->total_votes}</b>";

        $this->sendMessage($chatId, $message);
    }
}
