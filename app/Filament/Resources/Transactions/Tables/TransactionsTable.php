<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('botUser.id')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'deposit', 'bonus_loyalty', 'referral_commission', 'referral_bonus', 'refund' => 'success',
                        'charge', 'premium_charge', 'withdraw' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('wallet')
                    ->label('Кошелёк')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('usd')
                    ->color(fn ($state) => (float) $state >= 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('balance_after')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('sourceable_type')
                    ->searchable(),
                TextColumn::make('sourceable_id')
                    ->numeric()
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
                \Filament\Tables\Filters\SelectFilter::make('type')->label('Тип')->options([
                    'deposit' => 'Пополнение',
                    'charge' => 'Списание (проверка)',
                    'bonus_loyalty' => 'Бонус лояльности',
                    'referral_commission' => 'Реф. комиссия',
                    'referral_bonus' => 'Реф. бонус',
                    'withdraw' => 'Вывод',
                    'premium_charge' => 'Премиум',
                    'admin_adjust' => 'Корректировка',
                    'refund' => 'Возврат',
                ]),
                \Filament\Tables\Filters\SelectFilter::make('wallet')->label('Кошелёк')->options([
                    'deposit' => 'Депозит',
                    'referral' => 'Реф. баланс',
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
