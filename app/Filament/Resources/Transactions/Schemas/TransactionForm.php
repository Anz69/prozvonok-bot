<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bot_user_id')
                    ->relationship('botUser', 'id')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('wallet')
                    ->required()
                    ->default('deposit'),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('balance_after')
                    ->required()
                    ->numeric(),
                TextInput::make('description'),
                Textarea::make('meta')
                    ->columnSpanFull(),
                TextInput::make('sourceable_type'),
                TextInput::make('sourceable_id')
                    ->numeric(),
            ]);
    }
}
