<?php

namespace App\Filament\Resources\PollResource\Pages;

use App\Filament\Resources\PollResource;
use App\Models\Poll;
use Filament\Resources\Pages\Page;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPollResults extends Page
{
    protected static string $resource = PollResource::class;

    protected static string $view = 'filament.resources.poll-resource.pages.view-poll-results';

    public Poll $record;

    public function mount($record): void
    {
        $this->record = Poll::with(['candidates', 'votes', 'participants'])->findOrFail($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function getTitle(): string
    {
        return 'So\'rovnoma natijalari: ' . $this->record->title;
    }
}
