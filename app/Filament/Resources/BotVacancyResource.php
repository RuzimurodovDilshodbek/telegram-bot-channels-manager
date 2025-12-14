<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotVacancyResource\Pages;
use App\Filament\Resources\BotVacancyResource\RelationManagers;
use App\Models\BotVacancy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BotVacancyResource extends Resource
{
    protected static ?string $model = BotVacancy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('original_vacancy_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('source')
                    ->required()
                    ->maxLength(255)
                    ->default('oson-ish'),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('pending'),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('company_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('region_soato')
                    ->maxLength(10),
                Forms\Components\TextInput::make('region_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('district_soato')
                    ->maxLength(10),
                Forms\Components\TextInput::make('district_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('salary_min')
                    ->numeric(),
                Forms\Components\TextInput::make('salary_max')
                    ->numeric(),
                Forms\Components\TextInput::make('work_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('busyness_type')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('show_url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('raw_data'),
                Forms\Components\TextInput::make('management_message_id')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('published_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('original_vacancy_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('region_soato')
                    ->searchable(),
                Tables\Columns\TextColumn::make('region_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('district_soato')
                    ->searchable(),
                Tables\Columns\TextColumn::make('district_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('salary_min')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salary_max')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('work_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('busyness_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('show_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('management_message_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
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
            'index' => Pages\ListBotVacancies::route('/'),
            'create' => Pages\CreateBotVacancy::route('/create'),
            'edit' => Pages\EditBotVacancy::route('/{record}/edit'),
        ];
    }
}
