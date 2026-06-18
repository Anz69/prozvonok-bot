<?php

namespace App\Filament\Resources\LoyaltyBonuses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LoyaltyBonusForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('threshold')
                    ->required()
                    ->numeric(),
                TextInput::make('bonus')
                    ->required()
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('sort')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
