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
                    ->select('bot_vacancies.*')
                    ->selectRaw('(SELECT COALESCE(SUM(clicks_count), 0) FROM channel_posts WHERE channel_posts.bot_vacancy_id = bot_vacancies.id) as clicks_total')
                    ->where('status', 'published')
                    ->whereRaw('(SELECT COALESCE(SUM(clicks_count), 0) FROM channel_posts WHERE channel_posts.bot_vacancy_id = bot_vacancies.id) > 0')
                    ->orderByDesc('clicks_total')
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

                Tables\Columns\TextColumn::make('clicks_total')
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
            ->defaultSort('clicks_total', 'desc')
            ->paginated(false);
    }
}
