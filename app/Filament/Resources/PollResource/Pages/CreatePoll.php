<?php

namespace App\Filament\Resources\PollResource\Pages;

use App\Filament\Resources\PollResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePoll extends CreateRecord
{
    protected static string $resource = PollResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
