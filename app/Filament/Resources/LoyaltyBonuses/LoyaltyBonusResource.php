<?php

namespace App\Filament\Resources\LoyaltyBonuses;

use App\Filament\Resources\LoyaltyBonuses\Pages\CreateLoyaltyBonus;
use App\Filament\Resources\LoyaltyBonuses\Pages\EditLoyaltyBonus;
use App\Filament\Resources\LoyaltyBonuses\Pages\ListLoyaltyBonuses;
use App\Filament\Resources\LoyaltyBonuses\Schemas\LoyaltyBonusForm;
use App\Filament\Resources\LoyaltyBonuses\Tables\LoyaltyBonusesTable;
use App\Models\LoyaltyBonus;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LoyaltyBonusResource extends Resource
{
    protected static ?string $model = LoyaltyBonus::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Финансы';

    protected static ?string $navigationLabel = 'Бонусы лояльности';

    protected static ?string $pluralModelLabel = 'Бонусы лояльности';

    protected static ?string $modelLabel = 'бонус';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LoyaltyBonusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoyaltyBonusesTable::configure($table);
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
            'index' => ListLoyaltyBonuses::route('/'),
            'create' => CreateLoyaltyBonus::route('/create'),
            'edit' => EditLoyaltyBonus::route('/{record}/edit'),
        ];
    }
}
