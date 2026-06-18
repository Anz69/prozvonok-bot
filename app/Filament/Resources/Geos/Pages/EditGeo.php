<?php

namespace App\Filament\Resources\Geos\Pages;

use App\Filament\Resources\Geos\GeoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGeo extends EditRecord
{
    protected static string $resource = GeoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
