<?php

namespace App\Filament\Resources\Withdrawals\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WithdrawalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bot_user_id')
                    ->relationship('botUser', 'id')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('address')
                    ->required(),
                TextInput::make('network')
                    ->required()
                    ->default('TRC20'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('tx_hash'),
                Select::make('admin_id')
                    ->relationship('admin', 'name'),
                TextInput::make('reason'),
                DateTimePicker::make('processed_at'),
            ]);
    }
}
