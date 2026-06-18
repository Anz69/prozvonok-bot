<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bot_user_id')
                    ->relationship('botUser', 'id')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('general'),
                Textarea::make('message')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
            ]);
    }
}
