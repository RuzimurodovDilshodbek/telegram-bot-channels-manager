<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {--delete : Webhookni o\'chirish}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Telegram bot webhook o\'rnatish yoki o\'chirish';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegram): int
    {
        try {
            // Delete webhook if --delete option is provided
            if ($this->option('delete')) {
                $this->warn('Webhook o\'chirilmoqda...');
                $result = $telegram->deleteWebhook();

                if ($result) {
                    $this->info('✅ Webhook muvaffaqiyatli o\'chirildi');
                    return Command::SUCCESS;
                } else {
                    $this->error('❌ Webhook o\'chirishda xatolik!');
                    return Command::FAILURE;
                }
            }

            // Set webhook
            $webhookUrl = config('telegram.webhook.url');

            if (!$webhookUrl) {
                $this->error('Webhook URL sozlanmagan!');
                $this->line('config/telegram.php yoki .env faylida TELEGRAM_WEBHOOK_URL ni to\'ldiring');
                return Command::FAILURE;
            }

            $this->info('Webhook o\'rnatilmoqda...');
            $this->line('URL: ' . $webhookUrl);
            $this->newLine();

            $result = $telegram->setWebhook($webhookUrl, [
                'allowed_updates' => config('telegram.webhook.allowed_updates', []),
                'max_connections' => config('telegram.webhook.max_connections', 40),
            ]);

            if ($result) {
                $this->info('✅ Webhook muvaffaqiyatli o\'rnatildi!');
                $this->newLine();

                // Show webhook info
                $webhookInfo = $telegram->getWebhookInfo();
                if ($webhookInfo) {
                    $this->line('Webhook URL: ' . ($webhookInfo['url'] ?? '-'));
                    $this->line('Kutilayotgan yangilanishlar: ' . ($webhookInfo['pending_update_count'] ?? 0));
                }

                return Command::SUCCESS;
            } else {
                $this->error('❌ Webhook o\'rnatishda xatolik!');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Xatolik: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
