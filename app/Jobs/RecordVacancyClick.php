<?php

namespace App\Jobs;

use App\Models\ChannelPost;
use App\Models\VacancyClick;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordVacancyClick implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $channelPostId;
    protected array $clickData;

    /**
     * Create a new job instance.
     */
    public function __construct(int $channelPostId, array $clickData)
    {
        $this->channelPostId = $channelPostId;
        $this->clickData = $clickData;

        // Set queue based on config
        $this->onQueue(config('tracking.queue.queue_name', 'click-tracking'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        VacancyClick::create([
            'channel_post_id' => $this->channelPostId,
            'user_telegram_id' => $this->clickData['user_telegram_id'] ?? null,
            'ip_address' => $this->clickData['ip_address'] ?? null,
            'user_agent' => $this->clickData['user_agent'] ?? null,
            'referrer' => $this->clickData['referrer'] ?? null,
            'clicked_at' => now(),
        ]);
    }

    /**
     * The number of times the job may be attempted.
     */
    public function tries(): int
    {
        return config('tracking.queue.max_tries', 3);
    }

    /**
     * The number of seconds the job can run before timing out.
     */
    public function timeout(): int
    {
        return config('tracking.queue.retry_after', 90);
    }
}
