<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotVacancyResource\Pages;
use App\Filament\Resources\BotVacancyResource\RelationManagers;
use App\Models\BotVacancy;
use App\Services\VacancyPublisher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class BotVacancyResource extends Resource
{
    protected static ?string $model = BotVacancy::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Vakansiyalar';

    protected static ?string $modelLabel = 'Vakansiya';

    protected static ?string $pluralModelLabel = 'Vakansiyalar';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Asosiy ma\'lumotlar')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Vakansiya nomi')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Kompaniya')
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Kutilmoqda',
                                'approved' => 'Tasdiqlangan',
                                'rejected' => 'Rad etilgan',
                                'published' => 'Nashr qilingan',
                            ])
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('source')
                            ->label('Manba')
                            ->default('oson-ish')
                            ->disabled(),

                        Forms\Components\TextInput::make('original_vacancy_id')
                            ->label('Asl ID')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Joylashuv')
                    ->schema([
                        Forms\Components\TextInput::make('region_name')
                            ->label('Viloyat'),

                        Forms\Components\TextInput::make('region_soato')
                            ->label('Viloyat SOATO')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('district_name')
                            ->label('Tuman/Shahar'),

                        Forms\Components\TextInput::make('district_soato')
                            ->label('Tuman SOATO')
                            ->maxLength(20),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Ish sharoitlari')
                    ->schema([
                        Forms\Components\TextInput::make('salary_min')
                            ->label('Minimum maosh')
                            ->numeric()
                            ->suffix('so\'m'),

                        Forms\Components\TextInput::make('salary_max')
                            ->label('Maximum maosh')
                            ->numeric()
                            ->suffix('so\'m'),

                        Forms\Components\TextInput::make('work_type')
                            ->label('Ish turi'),

                        Forms\Components\TextInput::make('busyness_type')
                            ->label('Bandlik turi'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Qo\'shimcha')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Tavsif')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('show_url')
                            ->label('Havola')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('management_message_id')
                            ->label('Boshqaruv xabari ID')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Nashr vaqti')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'published',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Kutilmoqda',
                        'approved' => 'Tasdiqlangan',
                        'rejected' => 'Rad etilgan',
                        'published' => 'Nashr qilingan',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Vakansiya')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 40) {
                            return $state;
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Kompaniya')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('region_name')
                    ->label('Hudud')
                    ->formatStateUsing(fn ($record) =>
                        $record->region_name . ($record->district_name ? ', ' . $record->district_name : '')
                    )
                    ->searchable(['region_name', 'district_name'])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('formatted_salary')
                    ->label('Maosh')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('salary_min', $direction);
                    }),

                Tables\Columns\TextColumn::make('source')
                    ->label('Manba')
                    ->badge()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_clicks')
                    ->label('Bosishlar')
                    ->numeric()
                    ->sortable()
                    ->default(0),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Kelgan vaqt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Nashr vaqti')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Kutilmoqda',
                        'approved' => 'Tasdiqlangan',
                        'rejected' => 'Rad etilgan',
                        'published' => 'Nashr qilingan',
                    ]),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Manba')
                    ->options([
                        'oson-ish' => 'Oson Ish',
                    ]),

                Tables\Filters\Filter::make('region_soato')
                    ->label('Viloyat')
                    ->form([
                        Forms\Components\TextInput::make('region_soato')
                            ->label('SOATO kod'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['region_soato'],
                            fn (Builder $query, $soato): Builder => $query->where('region_soato', $soato),
                        );
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->label('Kelgan sana')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dan'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Gacha'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Tasdiqlash')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BotVacancy $record) => $record->status === 'pending')
                    ->authorize(fn (BotVacancy $record) => auth()->user()->can('approve', $record))
                    ->action(function (BotVacancy $record) {
                        try {
                            $publisher = app(VacancyPublisher::class);
                            $userId = auth()->id() ?? 1;
                            $success = $publisher->handleApproval($record, $userId);

                            if ($success) {
                                Notification::make()
                                    ->success()
                                    ->title('Tasdiqlandi')
                                    ->body('Vakansiya tasdiqlandi va kanallarga yuborildi!')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Qisman muvaffaqiyatli')
                                    ->body('Vakansiya tasdiqlandi, lekin kanallarga yuborishda xatolik yuz berdi.')
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Xatolik')
                                ->body($e->getMessage())
                                ->send();

                            Log::error('Approve vacancy error', [
                                'vacancy_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rad etish')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (BotVacancy $record) => $record->status === 'pending')
                    ->authorize(fn (BotVacancy $record) => auth()->user()->can('reject', $record))
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Sabab (ixtiyoriy)')
                            ->rows(3),
                    ])
                    ->action(function (BotVacancy $record, array $data) {
                        try {
                            $publisher = app(VacancyPublisher::class);
                            $userId = auth()->id() ?? 1;
                            $publisher->handleRejection($record, $userId, null, $data['comment'] ?? null);

                            Notification::make()
                                ->success()
                                ->title('Rad etildi')
                                ->body('Vakansiya rad etildi.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Xatolik')
                                ->body($e->getMessage())
                                ->send();

                            Log::error('Reject vacancy error', [
                                'vacancy_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }),

                Tables\Actions\ViewAction::make()
                    ->label('Ko\'rish'),

                Tables\Actions\Action::make('view_url')
                    ->label('Saytda ochish')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (BotVacancy $record) => $record->show_url)
                    ->openUrlInNewTab()
                    ->visible(fn (BotVacancy $record) => !empty($record->show_url)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Tanlanganlarni tasdiqlash')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->authorize(fn () => auth()->user()->can('viewApprovalActions', BotVacancy::class))
                        ->action(function (Collection $records) {
                            $publisher = app(VacancyPublisher::class);
                            $userId = auth()->id() ?? 1;
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    // Check individual authorization
                                    if (!auth()->user()->can('approve', $record)) {
                                        $failCount++;
                                        continue;
                                    }

                                    try {
                                        $publisher->handleApproval($record, $userId);
                                        $successCount++;
                                    } catch (\Exception $e) {
                                        $failCount++;
                                        Log::error('Bulk approve error', [
                                            'vacancy_id' => $record->id,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Bajarildi')
                                ->body("{$successCount} ta vakansiya tasdiqlandi." .
                                    ($failCount > 0 ? " {$failCount} ta xatolik." : ''))
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('reject_selected')
                        ->label('Tanlanganlarni rad etish')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->authorize(fn () => auth()->user()->can('viewApprovalActions', BotVacancy::class))
                        ->action(function (Collection $records) {
                            $publisher = app(VacancyPublisher::class);
                            $userId = auth()->id() ?? 1;
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    // Check individual authorization
                                    if (!auth()->user()->can('reject', $record)) {
                                        continue;
                                    }

                                    try {
                                        $publisher->handleRejection($record, $userId);
                                        $count++;
                                    } catch (\Exception $e) {
                                        Log::error('Bulk reject error', [
                                            'vacancy_id' => $record->id,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Rad etildi')
                                ->body("{$count} ta vakansiya rad etildi.")
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBotVacancies::route('/'),
            'create' => Pages\CreateBotVacancy::route('/create'),
            'edit' => Pages\EditBotVacancy::route('/{record}/edit'),
        ];
    }
}
