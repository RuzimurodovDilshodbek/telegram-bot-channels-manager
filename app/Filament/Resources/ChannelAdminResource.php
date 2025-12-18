<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChannelAdminResource\Pages;
use App\Models\ChannelAdmin;
use App\Models\Channel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ChannelAdminResource extends Resource
{
    protected static ?string $model = ChannelAdmin::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Adminlar';

    protected static ?string $modelLabel = 'Admin';

    protected static ?string $pluralModelLabel = 'Adminlar';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Sozlamalar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Admin ma\'lumotlari')
                    ->schema([
                        Forms\Components\Select::make('channel_id')
                            ->label('Kanal')
                            ->helperText('Qaysi kanal uchun admin ekanligini tanlang')
                            ->options(fn () => Channel::query()
                                ->orderBy('type')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($channel) => [
                                    $channel->id => ucfirst($channel->type) . ': ' . $channel->name
                                ]))
                            ->searchable()
                            ->required()
                            ->preload(),

                        Forms\Components\TextInput::make('name')
                            ->label('Ism')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ism Familiya'),

                        Forms\Components\TextInput::make('telegram_username')
                            ->label('Telegram username')
                            ->prefix('@')
                            ->maxLength(255)
                            ->placeholder('username'),

                        Forms\Components\TextInput::make('telegram_user_id')
                            ->label('Telegram User ID')
                            ->helperText('Telegram foydalanuvchi ID raqami (masalan: 123456789)')
                            ->required()
                            ->numeric()
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                return $rule->where('channel_id', $get('channel_id'));
                            })
                            ->placeholder('123456789'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Faol')
                            ->helperText('Admin faolligi')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Qo\'llanma')
                    ->schema([
                        Forms\Components\Placeholder::make('help')
                            ->label('')
                            ->content('Telegram User ID ni olish uchun:
1. @userinfobot botiga /start yuboring
2. Bot sizning user ID ingizni ko\'rsatadi
3. Yoki admin Telegram profiliga forward qilib user ID ni oling'),
                    ])
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

                Tables\Columns\TextColumn::make('channel.name')
                    ->label('Kanal')
                    ->formatStateUsing(fn ($record) =>
                        ucfirst($record->channel->type) . ': ' . $record->channel->name
                    )
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Ism')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('telegram_username')
                    ->label('Username')
                    ->formatStateUsing(fn (?string $state) => $state ? '@' . $state : '-')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('telegram_user_id')
                    ->label('Telegram ID')
                    ->copyable()
                    ->copyMessage('ID nusxalandi')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Faol')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Qo\'shilgan')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('O\'zgartirilgan')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel_id')
                    ->label('Kanal')
                    ->options(fn () => Channel::query()
                        ->orderBy('type')
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($channel) => [
                            $channel->id => ucfirst($channel->type) . ': ' . $channel->name
                        ])),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Faol holat')
                    ->placeholder('Hammasi')
                    ->trueLabel('Faol')
                    ->falseLabel('Nofaol'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (ChannelAdmin $record) => $record->is_active ? 'Nofaol qilish' : 'Faol qilish')
                    ->icon(fn (ChannelAdmin $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (ChannelAdmin $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (ChannelAdmin $record) {
                        $record->update(['is_active' => !$record->is_active]);

                        Notification::make()
                            ->success()
                            ->title('O\'zgartirildi')
                            ->body($record->is_active ? 'Admin faol qilindi' : 'Admin nofaol qilindi')
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Faol qilish')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);

                            Notification::make()
                                ->success()
                                ->title('Faol qilindi')
                                ->body(count($records) . ' ta admin faol qilindi')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nofaol qilish')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);

                            Notification::make()
                                ->success()
                                ->title('Nofaol qilindi')
                                ->body(count($records) . ' ta admin nofaol qilindi')
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChannelAdmins::route('/'),
            'create' => Pages\CreateChannelAdmin::route('/create'),
            'edit' => Pages\EditChannelAdmin::route('/{record}/edit'),
        ];
    }
}
