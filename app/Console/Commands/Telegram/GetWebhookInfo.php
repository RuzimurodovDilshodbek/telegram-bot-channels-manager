<?php

namespace App\Console\Commands\Telegram;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class GetWebhookInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:get-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Telegram bot webhook ma\'lumotlarini olish';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegram): int
    {
        $this->info('Webhook ma\'lumotlari olinmoqda...');
        $this->newLine();

        try {
            $webhookInfo = $telegram->getWebhookInfo();

            if (!$webhookInfo) {
                $this->error('Webhook ma\'lumotlarini olishda xatolik!');
                return Command::FAILURE;
            }

            $this->components->twoColumnDetail('Webhook URL', $webhookInfo['url'] ?? '-');
            $this->components->twoColumnDetail('Has Custom Certificate', $webhookInfo['has_custom_certificate'] ? 'Ha' : 'Yo\'q');
            $this->components->twoColumnDetail('Pending Update Count', $webhookInfo['pending_update_count'] ?? 0);

            if (isset($webhookInfo['last_error_date'])) {
                $lastError = date('d.m.Y H:i:s', $webhookInfo['last_error_date']);
                $this->components->twoColumnDetail('Oxirgi xatolik vaqti', $lastError);

                if (isset($webhookInfo['last_error_message'])) {
                    $this->newLine();
                    $this->warn('Oxirgi xatolik: ' . $webhookInfo['last_error_message']);
                }
            }

            if (isset($webhookInfo['last_synchronization_error_date'])) {
                $syncError = date('d.m.Y H:i:s', $webhookInfo['last_synchronization_error_date']);
                $this->components->twoColumnDetail('Oxirgi sinxronizatsiya xatosi', $syncError);
            }

            $this->components->twoColumnDetail('Max Connections', $webhookInfo['max_connections'] ?? '-');

            if (!empty($webhookInfo['allowed_updates'])) {
                $this->newLine();
                $this->info('Ruxsat etilgan yangilanishlar:');
                foreach ($webhookInfo['allowed_updates'] as $update) {
                    $this->line('  - ' . $update);
                }
            }

            $this->newLine();
            $this->info('✅ Webhook ma\'lumotlari muvaffaqiyatli olindi');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Xatolik: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
