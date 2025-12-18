<?php

namespace App\Filament\Resources\ChannelAdminResource\Pages;

use App\Filament\Resources\ChannelAdminResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChannelAdmins extends ListRecords
{
    protected static string $resource = ChannelAdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
