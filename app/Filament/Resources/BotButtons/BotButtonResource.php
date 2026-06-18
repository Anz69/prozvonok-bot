<?php

namespace App\Filament\Resources\BotButtons;

use App\Filament\Resources\BotButtons\Pages\CreateBotButton;
use App\Filament\Resources\BotButtons\Pages\EditBotButton;
use App\Filament\Resources\BotButtons\Pages\ListBotButtons;
use App\Filament\Resources\BotButtons\Schemas\BotButtonForm;
use App\Filament\Resources\BotButtons\Tables\BotButtonsTable;
use App\Models\BotButton;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BotButtonResource extends Resource
{
    protected static ?string $model = BotButton::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Кнопки меню';

    protected static ?string $pluralModelLabel = 'Кнопки меню';

    protected static ?string $modelLabel = 'кнопка';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BotButtonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BotButtonsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canAccess(): bool
    {
        // Контент/настройки — только для администратора (менеджер не видит)
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBotButtons::route('/'),
            'create' => CreateBotButton::route('/create'),
            'edit' => EditBotButton::route('/{record}/edit'),
        ];
    }
}
