<?php

namespace App\Filament\Resources\AdminAudits\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AdminAuditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('action')
                    ->required(),
                TextInput::make('auditable_type'),
                TextInput::make('auditable_id')
                    ->numeric(),
                Textarea::make('changes')
                    ->columnSpanFull(),
                TextInput::make('ip'),
            ]);
    }
}
