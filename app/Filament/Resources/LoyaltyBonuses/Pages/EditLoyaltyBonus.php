<?php

namespace App\Filament\Resources\LoyaltyBonuses\Pages;

use App\Filament\Resources\LoyaltyBonuses\LoyaltyBonusResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoyaltyBonus extends EditRecord
{
    protected static string $resource = LoyaltyBonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
