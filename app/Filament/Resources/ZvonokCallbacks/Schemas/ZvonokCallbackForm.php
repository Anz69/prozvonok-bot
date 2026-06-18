<?php

namespace App\Filament\Resources\ZvonokCallbacks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ZvonokCallbackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('check_job_id')
                    ->relationship('checkJob', 'id'),
                Select::make('check_number_id')
                    ->relationship('checkNumber', 'id'),
                Textarea::make('payload')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('processed')
                    ->required(),
            ]);
    }
}
