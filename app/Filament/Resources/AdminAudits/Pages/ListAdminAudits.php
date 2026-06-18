<?php

namespace App\Filament\Resources\AdminAudits\Pages;

use App\Filament\Resources\AdminAudits\AdminAuditResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdminAudits extends ListRecords
{
    protected static string $resource = AdminAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
