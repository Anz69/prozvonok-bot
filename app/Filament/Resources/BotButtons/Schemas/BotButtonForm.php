<?php

namespace App\Filament\Resources\BotButtons\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BotButtonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required(),
                TextInput::make('label')
                    ->required(),
                TextInput::make('menu')
                    ->required()
                    ->default('main'),
                TextInput::make('row')
                    ->label('Ряд (строка меню)')
                    ->helperText('Кнопки с одинаковым «рядом» стоят в одной строке.')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('action')
                    ->required(),
                TextInput::make('payload'),
                TextInput::make('sort')
                    ->label('Порядок в ряду')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('is_visible')
                    ->required(),
            ]);
    }
}
