<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollVoteResource\Pages;
use App\Models\PollVote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PollVoteResource extends Resource
{
    protected static ?string $model = PollVote::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'Ovozlar';

    protected static ?string $modelLabel = 'Ovoz';

    protected static ?string $pluralModelLabel = 'Ovozlar';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationGroup = 'So\'rovnoma tizimi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('poll_id')
                    ->label('So\'rovnoma')
                    ->relationship('poll', 'title')
                    ->required(),

                Forms\Components\Select::make('poll_candidate_id')
                    ->label('Nomzod')
                    ->relationship('candidate', 'name')
                    ->required(),

                Forms\Components\TextInput::make('chat_id')
                    ->label('Chat ID')
                    ->required(),

                Forms\Components\TextInput::make('ip_address')
                    ->label('IP manzil'),

                Forms\Components\DateTimePicker::make('voted_at')
                    ->label('Ovoz bergan vaqti')
                    ->required(),
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

                Tables\Columns\TextColumn::make('candidate.name')
                    ->label('Nomzod')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('participant.full_name')
                    ->label('Foydalanuvchi')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        $p = $record->participant;
                        return $p ? $p->full_name . ($p->username ? " (@{$p->username})" : '') : '-';
                    }),

                Tables\Columns\TextColumn::make('participant.phone')
                    ->label('Telefon')
                    ->searchable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP manzil')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('voted_at')
                    ->label('Ovoz bergan vaqti')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('poll_id')
                    ->label('So\'rovnoma')
                    ->relationship('poll', 'title'),

                Tables\Filters\SelectFilter::make('poll_candidate_id')
                    ->label('Nomzod')
                    ->relationship('candidate', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('voted_at', 'desc');
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
            'index' => Pages\ListPollVotes::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Ovozlar faqat bot orqali yaratiladi
    }
}
