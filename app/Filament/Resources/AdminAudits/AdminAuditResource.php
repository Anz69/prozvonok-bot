<?php

namespace App\Filament\Resources\AdminAudits;

use App\Filament\Resources\AdminAudits\Pages\CreateAdminAudit;
use App\Filament\Resources\AdminAudits\Pages\EditAdminAudit;
use App\Filament\Resources\AdminAudits\Pages\ListAdminAudits;
use App\Filament\Resources\AdminAudits\Schemas\AdminAuditForm;
use App\Filament\Resources\AdminAudits\Tables\AdminAuditsTable;
use App\Models\AdminAudit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdminAuditResource extends Resource
{
    protected static ?string $model = AdminAudit::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'Аудит действий';

    protected static ?string $pluralModelLabel = 'Аудит действий';

    protected static ?string $modelLabel = 'запись аудита';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AdminAuditForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminAuditsTable::configure($table);
    }

    // Журнал аудита — только чтение
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminAudits::route('/'),
        ];
    }
}
