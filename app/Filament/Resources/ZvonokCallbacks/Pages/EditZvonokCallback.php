<?php

namespace App\Filament\Resources\ZvonokCallbacks\Pages;

use App\Filament\Resources\ZvonokCallbacks\ZvonokCallbackResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditZvonokCallback extends EditRecord
{
    protected static string $resource = ZvonokCallbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
