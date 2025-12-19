<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollParticipantResource\Pages;
use App\Models\PollParticipant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PollParticipantResource extends Resource
{
    protected static ?string $model = PollParticipant::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Ishtirokchilar';

    protected static ?string $modelLabel = 'Ishtirokchi';

    protected static ?string $pluralModelLabel = 'Ishtirokchilar';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationGroup = 'So\'rovnoma tizimi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('poll_id')
                    ->label('So\'rovnoma')
                    ->relationship('poll', 'title')
                    ->required(),

                Forms\Components\TextInput::make('chat_id')
                    ->label('Chat ID')
                    ->required(),

                Forms\Components\TextInput::make('first_name')
                    ->label('Ism')
                    ->maxLength(255),

                Forms\Components\TextInput::make('last_name')
                    ->label('Familiya')
                    ->maxLength(255),

                Forms\Components\TextInput::make('username')
                    ->label('Username')
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->label('Telefon')
                    ->maxLength(255),

                Forms\Components\TextInput::make('ip_address')
                    ->label('IP manzil'),

                Forms\Components\Toggle::make('phone_verified')
                    ->label('Telefon tasdiqlangan'),

                Forms\Components\Toggle::make('subscription_verified')
                    ->label('Obuna tasdiqlangan'),

                Forms\Components\Toggle::make('recaptcha_verified')
                    ->label('ReCaptcha tasdiqlangan'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('poll.title')
                    ->label('So\'rovnoma')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Ism Familiya')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ? "@{$state}" : '-'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable(),

                Tables\Columns\IconColumn::make('phone_verified')
                    ->label('Tel. ✓')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('subscription_verified')
                    ->label('Obuna ✓')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vote.candidate.name')
                    ->label('Ovoz bergan nomzod')
                    ->default('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Qo\'shilgan vaqti')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('poll_id')
                    ->label('So\'rovnoma')
                    ->relationship('poll', 'title'),

                Tables\Filters\TernaryFilter::make('phone_verified')
                    ->label('Telefon tasdiqlangan'),

                Tables\Filters\TernaryFilter::make('subscription_verified')
                    ->label('Obuna tasdiqlangan'),

                Tables\Filters\Filter::make('has_voted')
                    ->label('Ovoz berganlar')
                    ->query(fn (Builder $query) => $query->whereHas('vote')),

                Tables\Filters\Filter::make('not_voted')
                    ->label('Ovoz bermaganlar')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('vote')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListPollParticipants::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Ishtirokchilar faqat bot orqali yaratiladi
    }
}
