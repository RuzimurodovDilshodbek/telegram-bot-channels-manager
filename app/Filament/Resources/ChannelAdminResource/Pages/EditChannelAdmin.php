<?php

namespace App\Filament\Resources\ChannelAdminResource\Pages;

use App\Filament\Resources\ChannelAdminResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChannelAdmin extends EditRecord
{
    protected static string $resource = ChannelAdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
