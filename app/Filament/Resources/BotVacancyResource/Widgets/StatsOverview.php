<?php

namespace App\Filament\Resources\BotVacancyResource\Widgets;

use App\Models\BotVacancy;
use App\Models\Channel;
use App\Models\VacancyClick;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $totalVacancies = BotVacancy::count();
        $pendingVacancies = BotVacancy::where('status', 'pending')->count();
        $publishedVacancies = BotVacancy::where('status', 'published')->count();
        $totalClicks = VacancyClick::count();
        $todayClicks = VacancyClick::whereDate('created_at', today())->count();
        $activeChannels = Channel::where('is_active', true)->count();

        return [
            Stat::make('Jami vakansiyalar', $totalVacancies)
                ->description('Barcha vakansiyalar soni')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),

            Stat::make('Kutilmoqda', $pendingVacancies)
                ->description('Tasdiq kutilayotgan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Nashr qilingan', $publishedVacancies)
                ->description('Kanallarda chop etilgan')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Jami bosishlar', number_format($totalClicks))
                ->description("Bugun: " . number_format($todayClicks))
                ->descriptionIcon('heroicon-m-cursor-arrow-ripple')
                ->color('info'),

            Stat::make('Faol kanallar', $activeChannels)
                ->description('Ishlaydigan kanallar')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('success'),

            Stat::make('O\'rtacha bosish', $publishedVacancies > 0 ? number_format($totalClicks / $publishedVacancies, 1) : '0')
                ->description('Har bir vakansiya uchun')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}
