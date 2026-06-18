<?php

namespace App\Filament\Resources\RequiredChannels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RequiredChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('chat_id')
                    ->required(),
                TextInput::make('title'),
                TextInput::make('url')
                    ->url(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('sort')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
