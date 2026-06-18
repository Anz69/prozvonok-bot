<?php

namespace App\Filament\Resources\LoyaltyBonuses\Pages;

use App\Filament\Resources\LoyaltyBonuses\LoyaltyBonusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoyaltyBonuses extends ListRecords
{
    protected static string $resource = LoyaltyBonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
