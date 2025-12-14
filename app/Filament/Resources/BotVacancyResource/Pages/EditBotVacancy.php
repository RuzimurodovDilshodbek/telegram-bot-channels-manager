<?php

namespace App\Filament\Resources\BotVacancyResource\Pages;

use App\Filament\Resources\BotVacancyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBotVacancy extends EditRecord
{
    protected static string $resource = BotVacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
