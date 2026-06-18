<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bot_user_id')
                    ->relationship('botUser', 'id')
                    ->required(),
                TextInput::make('uid')
                    ->required(),
                TextInput::make('address')
                    ->required(),
                TextInput::make('network')
                    ->required()
                    ->default('TRC20'),
                TextInput::make('amount_expected')
                    ->required()
                    ->numeric(),
                TextInput::make('amount_received')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('bonus_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('tx_hash'),
                TextInput::make('confirmations')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('expires_at'),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
