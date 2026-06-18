<?php

namespace App\Filament\Resources\ZvonokCallbacks;

use App\Filament\Resources\ZvonokCallbacks\Pages\CreateZvonokCallback;
use App\Filament\Resources\ZvonokCallbacks\Pages\EditZvonokCallback;
use App\Filament\Resources\ZvonokCallbacks\Pages\ListZvonokCallbacks;
use App\Filament\Resources\ZvonokCallbacks\Schemas\ZvonokCallbackForm;
use App\Filament\Resources\ZvonokCallbacks\Tables\ZvonokCallbacksTable;
use App\Models\ZvonokCallback;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ZvonokCallbackResource extends Resource
{
    protected static ?string $model = ZvonokCallback::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'Колбэки Звонок.com';

    protected static ?string $pluralModelLabel = 'Колбэки Звонок.com';

    protected static ?string $modelLabel = 'колбэк';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // Сырьё postback'ов — только чтение (аудит)
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ZvonokCallbackForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ZvonokCallbacksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZvonokCallbacks::route('/'),
        ];
    }
}
