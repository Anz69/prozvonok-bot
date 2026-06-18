<?php

namespace App\Filament\Resources\ZvonokCallbacks\Pages;

use App\Filament\Resources\ZvonokCallbacks\ZvonokCallbackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListZvonokCallbacks extends ListRecords
{
    protected static string $resource = ZvonokCallbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
