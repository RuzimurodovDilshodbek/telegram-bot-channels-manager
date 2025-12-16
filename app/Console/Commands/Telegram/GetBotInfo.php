<?php

namespace App\Console\Commands\Telegram;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class GetBotInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:bot-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Telegram bot ma\'lumotlarini ko\'rish';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegram): int
    {
        $this->info('Bot ma\'lumotlari olinmoqda...');
        $this->newLine();

        try {
            $botInfo = $telegram->getMe();

            if (!$botInfo) {
                $this->error('Bot ma\'lumotlarini olishda xatolik!');
                return Command::FAILURE;
            }

            $this->components->twoColumnDetail('Bot ID', $botInfo['id'] ?? '-');
            $this->components->twoColumnDetail('Bot nomi', $botInfo['first_name'] ?? '-');
            $this->components->twoColumnDetail('Bot username', '@' . ($botInfo['username'] ?? '-'));
            $this->components->twoColumnDetail('Bot turi', $botInfo['is_bot'] ? 'Bot' : 'Foydalanuvchi');

            if (isset($botInfo['can_join_groups'])) {
                $this->components->twoColumnDetail('Guruhlarga qo\'shilishi mumkin', $botInfo['can_join_groups'] ? 'Ha' : 'Yo\'q');
            }

            if (isset($botInfo['can_read_all_group_messages'])) {
                $this->components->twoColumnDetail('Guruh xabarlarini o\'qiy oladi', $botInfo['can_read_all_group_messages'] ? 'Ha' : 'Yo\'q');
            }

            if (isset($botInfo['supports_inline_queries'])) {
                $this->components->twoColumnDetail('Inline query qo\'llab-quvvatlaydi', $botInfo['supports_inline_queries'] ? 'Ha' : 'Yo\'q');
            }

            $this->newLine();
            $this->info('✅ Bot faol va ishlayapti!');
            $this->newLine();

            $this->line('Bot havolasi: https://t.me/' . ($botInfo['username'] ?? ''));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Xatolik: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Bot tokeni to\'g\'ri sozlanganligini tekshiring:');
            $this->line('  config/telegram.php -> bot_token');
            $this->line('  yoki .env -> TELEGRAM_BOT_TOKEN');
            return Command::FAILURE;
        }
    }
}
