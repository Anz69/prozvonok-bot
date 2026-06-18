<?php

namespace App\Filament\Resources\CheckJobs\Tables;

use App\Jobs\ProcessCheckJob;
use App\Models\CheckJob;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CheckJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('botUser.id')
                    ->searchable(),
                TextColumn::make('geo_code')
                    ->searchable(),
                TextColumn::make('numbers_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('numbers_valid')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('discount_percent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('free_applied')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        CheckJob::STATUS_COMPLETED => 'success',
                        CheckJob::STATUS_PROCESSING, CheckJob::STATUS_QUEUED => 'info',
                        CheckJob::STATUS_SCHEDULED => 'warning',
                        CheckJob::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('input_path')
                    ->searchable(),
                TextColumn::make('output_path')
                    ->searchable(),
                TextColumn::make('zvonok_campaign_id')
                    ->searchable(),
                TextColumn::make('queued_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('scheduled_for')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')->label('Статус')->options([
                    CheckJob::STATUS_QUEUED => 'В очереди',
                    CheckJob::STATUS_SCHEDULED => 'Отложено',
                    CheckJob::STATUS_PROCESSING => 'Обработка',
                    CheckJob::STATUS_COMPLETED => 'Завершено',
                    CheckJob::STATUS_FAILED => 'Ошибка',
                    CheckJob::STATUS_CANCELLED => 'Отменено',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('reprocess')
                    ->label('Повторить обработку')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (CheckJob $r) => in_array($r->status, [CheckJob::STATUS_FAILED, CheckJob::STATUS_QUEUED, CheckJob::STATUS_PROCESSING], true))
                    ->action(function (CheckJob $record): void {
                        $record->update(['status' => CheckJob::STATUS_QUEUED, 'queued_at' => now()]);
                        ProcessCheckJob::dispatch($record->id);
                        Notification::make()->title('Задание поставлено в обработку повторно')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
