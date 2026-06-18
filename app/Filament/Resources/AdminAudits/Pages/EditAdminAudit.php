<?php

namespace App\Filament\Resources\AdminAudits\Pages;

use App\Filament\Resources\AdminAudits\AdminAuditResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminAudit extends EditRecord
{
    protected static string $resource = AdminAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
