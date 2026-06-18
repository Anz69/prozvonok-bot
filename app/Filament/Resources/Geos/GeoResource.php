<?php

namespace App\Filament\Resources\Geos;

use App\Filament\Resources\Geos\Pages\CreateGeo;
use App\Filament\Resources\Geos\Pages\EditGeo;
use App\Filament\Resources\Geos\Pages\ListGeos;
use App\Filament\Resources\Geos\Schemas\GeoForm;
use App\Filament\Resources\Geos\Tables\GeosTable;
use App\Models\Geo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GeoResource extends Resource
{
    protected static ?string $model = Geo::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Финансы';

    protected static ?string $navigationLabel = 'ГЕО и тарифы';

    protected static ?string $pluralModelLabel = 'ГЕО и тарифы';

    protected static ?string $modelLabel = 'ГЕО';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return GeoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GeosTable::configure($table);
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
            'index' => ListGeos::route('/'),
            'create' => CreateGeo::route('/create'),
            'edit' => EditGeo::route('/{record}/edit'),
        ];
    }
}
