<?php

namespace App\Filament\Resources\ChannelAdminResource\Pages;

use App\Filament\Resources\ChannelAdminResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChannelAdmin extends CreateRecord
{
    protected static string $resource = ChannelAdminResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
