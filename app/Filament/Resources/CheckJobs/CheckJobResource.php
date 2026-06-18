<?php

namespace App\Filament\Resources\CheckJobs;

use App\Filament\Resources\CheckJobs\Pages\CreateCheckJob;
use App\Filament\Resources\CheckJobs\Pages\EditCheckJob;
use App\Filament\Resources\CheckJobs\Pages\ListCheckJobs;
use App\Filament\Resources\CheckJobs\Schemas\CheckJobForm;
use App\Filament\Resources\CheckJobs\Tables\CheckJobsTable;
use App\Models\CheckJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CheckJobResource extends Resource
{
    protected static ?string $model = CheckJob::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Проверки';

    protected static ?string $navigationLabel = 'Задания проверки';

    protected static ?string $pluralModelLabel = 'Задания проверки';

    protected static ?string $modelLabel = 'задание';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CheckJobForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckJobsTable::configure($table);
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
            'index' => ListCheckJobs::route('/'),
            'create' => CreateCheckJob::route('/create'),
            'edit' => EditCheckJob::route('/{record}/edit'),
        ];
    }
}
