<?php

namespace App\Filament\Resources\CheckJobs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CheckJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bot_user_id')
                    ->relationship('botUser', 'id')
                    ->required(),
                TextInput::make('geo_code')
                    ->required(),
                TextInput::make('numbers_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('numbers_valid')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('cost')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                TextInput::make('discount_percent')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('free_applied')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('input_path'),
                TextInput::make('output_path'),
                Textarea::make('summary')
                    ->columnSpanFull(),
                TextInput::make('zvonok_campaign_id'),
                DateTimePicker::make('queued_at'),
                DateTimePicker::make('scheduled_for'),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
