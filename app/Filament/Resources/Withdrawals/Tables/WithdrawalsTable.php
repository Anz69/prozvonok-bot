<?php

namespace App\Filament\Resources\Withdrawals\Tables;

use App\Models\AdminAudit;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Services\BalanceService;
use App\Telegram\Support\Notifier;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WithdrawalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('botUser.id')
                    ->searchable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('network')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Withdrawal::STATUS_PAID, Withdrawal::STATUS_APPROVED => 'success',
                        Withdrawal::STATUS_PENDING => 'warning',
                        Withdrawal::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('tx_hash')
                    ->searchable(),
                TextColumn::make('admin.name')
                    ->searchable(),
                TextColumn::make('reason')
                    ->searchable(),
                TextColumn::make('processed_at')
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
                    Withdrawal::STATUS_PENDING => 'Ожидает',
                    Withdrawal::STATUS_APPROVED => 'Одобрено',
                    Withdrawal::STATUS_PAID => 'Выплачено',
                    Withdrawal::STATUS_REJECTED => 'Отклонено',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Withdrawal $r) => $r->status === Withdrawal::STATUS_PENDING)
                    ->schema([
                        TextInput::make('tx_hash')->label('Хэш транзакции выплаты')->required(),
                    ])
                    ->action(function (Withdrawal $record, array $data): void {
                        $record->update([
                            'status' => Withdrawal::STATUS_PAID,
                            'tx_hash' => $data['tx_hash'],
                            'admin_id' => auth()->id(),
                            'processed_at' => now(),
                        ]);
                        AdminAudit::log('withdrawal_approve', $record, ['tx_hash' => $data['tx_hash']]);

                        app(Notifier::class)->notify(
                            $record->botUser,
                            "✅ Вывод {$record->amount}\$ выполнен. Хэш: {$data['tx_hash']}",
                        );

                        Notification::make()->title('Вывод одобрен')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Withdrawal $r) => $r->status === Withdrawal::STATUS_PENDING)
                    ->schema([
                        TextInput::make('reason')->label('Причина отклонения')->required(),
                    ])
                    ->action(function (Withdrawal $record, array $data): void {
                        // Возврат зарезервированных средств на реф.баланс
                        app(BalanceService::class)->credit(
                            $record->botUser,
                            (float) $record->amount,
                            Transaction::TYPE_REFUND,
                            Transaction::WALLET_REFERRAL,
                            "Возврат по отклонённой заявке #{$record->id}",
                            $record,
                        );
                        $record->update([
                            'status' => Withdrawal::STATUS_REJECTED,
                            'admin_id' => auth()->id(),
                            'reason' => $data['reason'],
                            'processed_at' => now(),
                        ]);
                        AdminAudit::log('withdrawal_reject', $record, ['reason' => $data['reason']]);

                        app(Notifier::class)->notify(
                            $record->botUser,
                            "❌ Заявка на вывод отклонена: {$data['reason']}. Средства возвращены на реф. баланс.",
                        );

                        Notification::make()->title('Вывод отклонён, средства возвращены')->warning()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
