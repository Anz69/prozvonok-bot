<?php

namespace App\Filament\Resources\AdminAudits\Pages;

use App\Filament\Resources\AdminAudits\AdminAuditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminAudit extends CreateRecord
{
    protected static string $resource = AdminAuditResource::class;
}
