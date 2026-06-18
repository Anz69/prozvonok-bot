<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\Payment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('botUser.id')
                    ->searchable(),
                TextColumn::make('uid')
                    ->searchable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('network')
                    ->searchable(),
                TextColumn::make('amount_expected')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_received')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bonus_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Payment::STATUS_PAID, Payment::STATUS_OVERPAID => 'success',
                        Payment::STATUS_UNDERPAID => 'warning',
                        Payment::STATUS_PENDING => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('tx_hash')
                    ->searchable(),
                TextColumn::make('confirmations')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('paid_at')
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
                SelectFilter::make('status')->label('Статус')->options([
                    Payment::STATUS_PENDING => 'Ожидает',
                    Payment::STATUS_PAID => 'Оплачен',
                    Payment::STATUS_UNDERPAID => 'Недоплата',
                    Payment::STATUS_OVERPAID => 'Переплата',
                    Payment::STATUS_EXPIRED => 'Просрочен',
                    Payment::STATUS_CANCELLED => 'Отменён',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
