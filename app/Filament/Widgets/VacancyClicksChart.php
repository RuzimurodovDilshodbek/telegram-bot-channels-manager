<?php

namespace App\Filament\Widgets;

use App\Models\VacancyClick;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class VacancyClicksChart extends ChartWidget
{
    protected static ?string $heading = 'Bosishlar statistikasi (so\'nggi 7 kun)';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Get last 7 days data
        $data = VacancyClick::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $values = [];

        // Fill in all 7 days, including days with 0 clicks
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayName = now()->subDays($i)->locale('uz_Latn')->isoFormat('dd, D-MMM');

            $clickCount = $data->where('date', $date)->first();

            $labels[] = $dayName;
            $values[] = $clickCount ? $clickCount->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Bosishlar soni',
                    'data' => $values,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
