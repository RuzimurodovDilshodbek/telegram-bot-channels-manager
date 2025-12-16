<?php

namespace App\Filament\Widgets;

use App\Models\BotVacancy;
use App\Models\ChannelPost;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopVacanciesTable extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Eng ko\'p bosilgan vakansiyalar (TOP 10)')
            ->query(
                BotVacancy::query()
                    ->where('status', 'published')
                    ->withCount(['channelPosts as total_clicks' => function ($query) {
                        $query->select(\DB::raw('COALESCE(SUM(clicks_count), 0)'));
                    }])
                    ->having('total_clicks', '>', 0)
                    ->orderByDesc('total_clicks')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Vakansiya')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Kompaniya')
                    ->searchable()
                    ->limit(30)
                    ->default('-'),

                Tables\Columns\TextColumn::make('region_name')
                    ->label('Hudud')
                    ->formatStateUsing(fn ($record) =>
                        $record->region_name . ($record->district_name ? ', ' . $record->district_name : '')
                    )
                    ->default('-'),

                Tables\Columns\TextColumn::make('total_clicks')
                    ->label('Bosishlar')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Nashr vaqti')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('total_clicks', 'desc')
            ->paginated(false);
    }
}
