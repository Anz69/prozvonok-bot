<?php

namespace App\Filament\Resources\BotUsers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BotUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('telegram_id')
                    ->tel()
                    ->required()
                    ->numeric(),
                TextInput::make('username'),
                TextInput::make('first_name'),
                TextInput::make('last_name'),
                TextInput::make('language_code'),
                TextInput::make('timezone')
                    ->required()
                    ->default('Europe/Moscow'),
                TextInput::make('deposit_balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('referral_balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_deposited')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('referral_percent')
                    ->required()
                    ->numeric()
                    ->default(5),
                TextInput::make('check_discount')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('referrer_id')
                    ->relationship('referrer', 'id'),
                TextInput::make('premium_tier'),
                DateTimePicker::make('premium_until'),
                Toggle::make('premium_auto_renew')
                    ->required(),
                Toggle::make('withdraw_unlocked')
                    ->required(),
                Toggle::make('is_subscribed')
                    ->required(),
                Toggle::make('passed_captcha')
                    ->required(),
                Toggle::make('used_free_numbers')
                    ->required(),
                TextInput::make('numbers_checked')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('numbers_answered')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('numbers_failed')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('files_checked')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('state')
                    ->columnSpanFull(),
                Toggle::make('is_banned')
                    ->required(),
            ]);
    }
}
