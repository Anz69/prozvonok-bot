<?php

namespace App\Filament\Resources\CheckJobs\Pages;

use App\Filament\Resources\CheckJobs\CheckJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheckJobs extends ListRecords
{
    protected static string $resource = CheckJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
