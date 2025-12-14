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
    protected $signature = 'app:telegram-set-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $telegramService = app(TelegramBotService::class);
        $webhookUrl = route('telegram.webhook');

        $result = $telegramService->setWebhook($webhookUrl);

        if ($result) {
            $this->info("Webhook o'rnatildi: {$webhookUrl}");
        } else {
            $this->error("Webhook o'rnatishda xatolik!");
        }
    }
}
