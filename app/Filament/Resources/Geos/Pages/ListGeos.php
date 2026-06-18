<?php

namespace App\Filament\Resources\Geos\Pages;

use App\Filament\Resources\Geos\GeoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGeos extends ListRecords
{
    protected static string $resource = GeoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
