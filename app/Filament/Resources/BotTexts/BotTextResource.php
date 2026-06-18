<?php

namespace App\Filament\Resources\BotTexts;

use App\Filament\Resources\BotTexts\Pages\CreateBotText;
use App\Filament\Resources\BotTexts\Pages\EditBotText;
use App\Filament\Resources\BotTexts\Pages\ListBotTexts;
use App\Filament\Resources\BotTexts\Schemas\BotTextForm;
use App\Filament\Resources\BotTexts\Tables\BotTextsTable;
use App\Models\BotText;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BotTextResource extends Resource
{
    protected static ?string $model = BotText::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Тексты бота';

    protected static ?string $pluralModelLabel = 'Тексты бота';

    protected static ?string $modelLabel = 'текст';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BotTextForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BotTextsTable::configure($table);
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
            'index' => ListBotTexts::route('/'),
            'create' => CreateBotText::route('/create'),
            'edit' => EditBotText::route('/{record}/edit'),
        ];
    }
}
