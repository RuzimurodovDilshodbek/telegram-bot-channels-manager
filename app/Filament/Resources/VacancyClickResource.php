<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacancyClickResource\Pages;
use App\Filament\Resources\VacancyClickResource\RelationManagers;
use App\Models\VacancyClick;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VacancyClickResource extends Resource
{
    protected static ?string $model = VacancyClick::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('channel_post_id')
                    ->relationship('channelPost', 'id')
                    ->required(),
                Forms\Components\TextInput::make('user_telegram_id')
                    ->tel()
                    ->numeric(),
                Forms\Components\TextInput::make('ip_address')
                    ->maxLength(45),
                Forms\Components\Textarea::make('user_agent')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('referrer')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('clicked_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('channelPost.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_telegram_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('referrer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('clicked_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListVacancyClicks::route('/'),
            'create' => Pages\CreateVacancyClick::route('/create'),
            'edit' => Pages\EditVacancyClick::route('/{record}/edit'),
        ];
    }
}
