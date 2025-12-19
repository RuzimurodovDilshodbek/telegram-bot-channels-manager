<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollResource\Pages;
use App\Models\Poll;
use App\Models\Channel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'So\'rovnomalar';

    protected static ?string $modelLabel = 'So\'rovnoma';

    protected static ?string $pluralModelLabel = 'So\'rovnomalar';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationGroup = 'So\'rovnoma tizimi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Asosiy ma\'lumotlar')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('So\'rovnoma nomi')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('description')
                            ->label('Tavsif')
                            ->rows(3)
                            ->columnSpan(2),

                        Forms\Components\FileUpload::make('image')
                            ->label('Rasm')
                            ->image()
                            ->directory('polls')
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Vaqt sozlamalari')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Boshlanish vaqti')
                            ->required()
                            ->default(now())
                            ->native(false),

                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('Tugash vaqti')
                            ->required()
                            ->after('start_date')
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Nomzodlar')
                    ->schema([
                        Forms\Components\Repeater::make('candidates')
                            ->relationship('candidates')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nomzod ismi')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('description')
                                    ->label('Ma\'lumot')
                                    ->rows(2),

                                Forms\Components\FileUpload::make('photo')
                                    ->label('Rasm')
                                    ->image()
                                    ->directory('poll-candidates'),

                                Forms\Components\TextInput::make('order')
                                    ->label('Tartib')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Faol')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->minItems(2)
                            ->defaultItems(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel('Nomzod qo\'shish')
                            ->reorderable('order'),
                    ]),

                Forms\Components\Section::make('Sozlamalar')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('So\'rovnoma faol')
                            ->default(true),

                        Forms\Components\Toggle::make('require_phone')
                            ->label('Telefon raqam talab qilish')
                            ->default(true),

                        Forms\Components\Toggle::make('require_subscription')
                            ->label('Kanal obunasi talab qilish')
                            ->default(true)
                            ->reactive(),

                        Forms\Components\Toggle::make('enable_recaptcha')
                            ->label('ReCaptcha yoqish')
                            ->default(true),

                        Forms\Components\Select::make('required_channels')
                            ->label('Majburiy kanallar')
                            ->multiple()
                            ->options(function () {
                                return Channel::where('is_active', true)
                                    ->pluck('name', 'chat_id');
                            })
                            ->visible(fn (Forms\Get $get) => $get('require_subscription'))
                            ->helperText('Foydalanuvchilar ovoz berishdan oldin ushbu kanallarga obuna bo\'lishlari kerak'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Nomi')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Boshlanish')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Tugash')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('candidates_count')
                    ->label('Nomzodlar')
                    ->counts('candidates')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_votes')
                    ->label('Jami ovozlar')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Faol')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Yaratilgan')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Faol so\'rovnomalar'),

                Tables\Filters\Filter::make('active_now')
                    ->label('Hozir faol')
                    ->query(fn (Builder $query) => $query->where('is_active', true)
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now())),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_results')
                    ->label('Natijalar')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->url(fn (Poll $record): string => PollResource::getUrl('results', ['record' => $record])),

                Tables\Actions\Action::make('publish_to_channels')
                    ->label('Kanallarga chiqarish')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('channels')
                            ->label('Kanallar')
                            ->multiple()
                            ->required()
                            ->options(function () {
                                return Channel::where('is_active', true)
                                    ->pluck('name', 'chat_id');
                            })
                            ->helperText('So\'rovnoma qaysi kanallarga chiqarilsin?'),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Poll $record, array $data) {
                        $pollBotService = app(\App\Services\PollBotService::class);

                        try {
                            $pollBotService->publishToChannels($record, $data['channels']);

                            Notification::make()
                                ->title('So\'rovnoma kanallarga chiqarildi!')
                                ->body(count($data['channels']) . ' ta kanalga post yuborildi.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Xatolik!')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListPolls::route('/'),
            'create' => Pages\CreatePoll::route('/create'),
            'edit' => Pages\EditPoll::route('/{record}/edit'),
            'results' => Pages\ViewPollResults::route('/{record}/results'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
