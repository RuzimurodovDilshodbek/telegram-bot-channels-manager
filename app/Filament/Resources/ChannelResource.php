<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChannelResource\Pages;
use App\Filament\Resources\ChannelResource\RelationManagers;
use App\Models\Channel;
use App\Models\Region;
use App\Services\TelegramBotService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class ChannelResource extends Resource
{
    protected static ?string $model = Channel::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Kanallar';

    protected static ?string $modelLabel = 'Kanal';

    protected static ?string $pluralModelLabel = 'Kanallar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kanal ma\'lumotlari')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Kanal turi')
                            ->options([
                                'management' => 'Boshqaruv kanali',
                                'main' => 'Asosiy kanal',
                                'region' => 'Hududiy kanal',
                                'poll' => 'So\'rovnoma kanali',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $state !== 'region' ? $set('region_soato', null) : null
                            ),

                        Forms\Components\TextInput::make('telegram_chat_id')
                            ->label('Telegram Chat ID')
                            ->helperText('Misol: -1001234567890')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('name')
                            ->label('Kanal nomi')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('username')
                            ->label('Kanal username')
                            ->helperText('@siz kanal usernamesiz')
                            ->prefix('@')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Qo\'shimcha sozlamalar')
                    ->schema([
                        Forms\Components\Select::make('region_soato')
                            ->label('Hududlar')
                            ->helperText('Bir yoki bir nechta hududni tanlang')
                            ->options(fn () => \App\Models\Region::query()->orderBy('name_uz')->pluck('name_uz', 'soato'))
                            ->searchable()
                            ->multiple()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'region')
                            ->required(fn (Forms\Get $get) => $get('type') === 'region'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Faol')
                            ->default(true)
                            ->required(),

                        Forms\Components\TextInput::make('posts_count')
                            ->label('Postlar soni')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Tavsif')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Turi')
                    ->colors([
                        'danger' => 'management',
                        'success' => 'main',
                        'primary' => 'region',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'management' => 'Boshqaruv',
                        'main' => 'Asosiy',
                        'region' => 'Hududiy',
                        default => $state,
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->formatStateUsing(fn (?string $state) => $state ? '@' . $state : '-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('telegram_chat_id')
                    ->label('Chat ID')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('region_soato')
                    ->label('Hududlar')
                    ->formatStateUsing(function ($state, $record) {
                        if (empty($state) || !is_array($state)) {
                            return '-';
                        }

                        $regions = Region::whereIn('soato', $state)
                            ->pluck('name_uz')
                            ->toArray();

                        return !empty($regions) ? implode(', ', $regions) : '-';
                    })
                    ->searchable()
                    ->tooltip(function ($state) {
                        if (empty($state) || !is_array($state)) {
                            return null;
                        }
                        return 'SOATO: ' . implode(', ', $state);
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Faol')
                    ->boolean(),

                Tables\Columns\TextColumn::make('posts_count')
                    ->label('Postlar')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Yaratilgan')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Kanal turi')
                    ->options([
                        'management' => 'Boshqaruv',
                        'main' => 'Asosiy',
                        'region' => 'Hududiy',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Faol holat')
                    ->placeholder('Hammasi')
                    ->trueLabel('Faol')
                    ->falseLabel('Nofaol'),
            ])
            ->actions([
                Tables\Actions\Action::make('make_admin')
                    ->label('Bot admin qilish')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Botni admin qilish')
                    ->modalDescription(fn (Channel $record) =>
                        "Botni {$record->name} kanaliga admin sifatida qo'shish uchun:\n\n" .
                        "1. Telegram'da {$record->name} kanaliga o'ting\n" .
                        "2. Kanal sozlamalarini oching\n" .
                        "3. Administrators > Add admin\n" .
                        "4. Botni qidiring va admin qiling\n" .
                        "5. Kerakli ruxsatlarni bering:\n" .
                        "   - Post messages\n" .
                        "   - Edit messages\n" .
                        "   - Delete messages\n\n" .
                        "Tayyor bo'lgandan keyin 'Tasdiqlash' tugmasini bosing."
                    )
                    ->modalSubmitActionLabel('Tasdiqlash')
                    ->action(function (Channel $record) {
                        try {
                            $telegram = app(TelegramBotService::class);
                            $result = $telegram->getMe();

                            if ($result) {
                                Notification::make()
                                    ->success()
                                    ->title('Bot faol')
                                    ->body("Bot (@{$result['username']}) faol va tayyor. Iltimos, botni kanalga admin qilganingizga ishonch hosil qiling.")
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Xatolik')
                                    ->body('Bot bilan bog\'lanib bo\'lmadi. Token to\'g\'riligini tekshiring.')
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Xatolik')
                                ->body($e->getMessage())
                                ->send();

                            Log::error('Make admin action error', [
                                'channel_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }),

                Tables\Actions\Action::make('test_connection')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (Channel $record) {
                        try {
                            $telegram = app(TelegramBotService::class);
                            $result = $telegram->sendMessage(
                                $record->telegram_chat_id,
                                "🤖 Test xabar\n\nKanal: {$record->name}\nVaqt: " . now()->format('d.m.Y H:i:s')
                            );

                            if ($result && isset($result['message_id'])) {
                                Notification::make()
                                    ->success()
                                    ->title('Ulanish muvaffaqiyatli')
                                    ->body('Test xabar kanalga yuborildi!')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Xatolik')
                                    ->body('Test xabar yuborilmadi. Bot kanalga admin emasmi?')
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Ulanish xatosi')
                                ->body($e->getMessage())
                                ->send();

                            Log::error('Channel connection test error', [
                                'channel_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListChannels::route('/'),
            'create' => Pages\CreateChannel::route('/create'),
            'edit' => Pages\EditChannel::route('/{record}/edit'),
        ];
    }
}
