<?php

namespace App\Filament\Resources\VacancyClickResource\Pages;

use App\Filament\Resources\VacancyClickResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVacancyClicks extends ListRecords
{
    protected static string $resource = VacancyClickResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
