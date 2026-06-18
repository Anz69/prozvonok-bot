<?php

namespace App\Filament\Resources\BotUsers\Tables;

use App\Models\AdminAudit;
use App\Models\BotUser;
use App\Models\Transaction;
use App\Services\BalanceService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BotUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('telegram_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('first_name')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->searchable(),
                TextColumn::make('language_code')
                    ->searchable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('deposit_balance')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('referral_balance')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_deposited')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('referral_percent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('check_discount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('referrer.id')
                    ->searchable(),
                TextColumn::make('premium_tier')
                    ->searchable(),
                TextColumn::make('premium_until')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('premium_auto_renew')
                    ->boolean(),
                IconColumn::make('withdraw_unlocked')
                    ->boolean(),
                IconColumn::make('is_subscribed')
                    ->boolean(),
                IconColumn::make('passed_captcha')
                    ->boolean(),
                IconColumn::make('used_free_numbers')
                    ->boolean(),
                TextColumn::make('numbers_checked')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('numbers_answered')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('numbers_failed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('files_checked')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_banned')
                    ->boolean(),
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
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('adjustBalance')
                    ->label('Корректировка баланса')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->schema([
                        Select::make('wallet')
                            ->label('Кошелёк')
                            ->options([
                                Transaction::WALLET_DEPOSIT => 'Депозит',
                                Transaction::WALLET_REFERRAL => 'Реф. баланс',
                            ])
                            ->default(Transaction::WALLET_DEPOSIT)
                            ->required(),
                        TextInput::make('amount')
                            ->label('Сумма (+ зачислить / − списать)')
                            ->numeric()
                            ->required(),
                        TextInput::make('note')
                            ->label('Комментарий')
                            ->maxLength(255),
                    ])
                    ->action(function (BotUser $record, array $data): void {
                        $balance = app(BalanceService::class);
                        $amount = (float) $data['amount'];
                        $wallet = $data['wallet'];
                        $desc = $data['note'] ?: 'Ручная корректировка администратором';

                        $tx = $amount >= 0
                            ? $balance->credit($record, $amount, Transaction::TYPE_ADMIN_ADJUST, $wallet, $desc)
                            : $balance->debit($record, abs($amount), Transaction::TYPE_ADMIN_ADJUST, $wallet, $desc);

                        AdminAudit::log('balance_adjust', $record, [
                            'amount' => $amount,
                            'wallet' => $wallet,
                            'balance_after' => $tx->balance_after,
                        ]);

                        Notification::make()
                            ->title('Баланс скорректирован')
                            ->body("Новый баланс: {$tx->balance_after}\$")
                            ->success()
                            ->send();
                    }),
                Action::make('grantPremium')
                    ->label('Выдать премиум')
                    ->icon('heroicon-o-star')
                    ->color('info')
                    ->schema([
                        Select::make('tier')
                            ->label('Уровень')
                            ->options([
                                'premium' => 'Премиум',
                                'premium_plus' => 'Премиум+',
                            ])
                            ->default('premium')
                            ->required(),
                        TextInput::make('days')
                            ->label('Дней')
                            ->numeric()
                            ->default(30)
                            ->required(),
                    ])
                    ->action(function (BotUser $record, array $data): void {
                        $discount = $data['tier'] === 'premium_plus'
                            ? (int) \App\Models\Setting::get('premium_plus_discount', 35)
                            : (int) \App\Models\Setting::get('premium_discount', 25);

                        $record->update([
                            'premium_tier' => $data['tier'],
                            'premium_until' => now()->addDays((int) $data['days']),
                            'check_discount' => $discount,
                        ]);
                        AdminAudit::log('premium_grant', $record, $data);

                        Notification::make()->title('Премиум выдан')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
