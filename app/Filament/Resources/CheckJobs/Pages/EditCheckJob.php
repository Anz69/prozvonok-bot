<?php

namespace App\Filament\Resources\CheckJobs\Pages;

use App\Filament\Resources\CheckJobs\CheckJobResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCheckJob extends EditRecord
{
    protected static string $resource = CheckJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
