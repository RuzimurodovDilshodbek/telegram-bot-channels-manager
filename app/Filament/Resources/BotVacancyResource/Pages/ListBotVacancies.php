<?php

namespace App\Filament\Resources\BotVacancyResource\Pages;

use App\Filament\Resources\BotVacancyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBotVacancies extends ListRecords
{
    protected static string $resource = BotVacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
