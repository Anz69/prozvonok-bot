<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use App\Models\AdminAudit;
use App\Models\SupportTicket;
use App\Telegram\Support\Notifier;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('botUser.telegram_id')->label('Telegram ID')->searchable(),
                TextColumn::make('type')->label('Тип')->badge()->color(fn (string $state) => match ($state) {
                    SupportTicket::TYPE_PERCENT_REQUEST => 'warning',
                    SupportTicket::TYPE_TRIAL_PREMIUM => 'info',
                    SupportTicket::TYPE_WITHDRAW_QUESTION => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('message')->label('Сообщение')->limit(60)->wrap(),
                TextColumn::make('status')->label('Статус')->badge()
                    ->color(fn (string $state) => $state === 'open' ? 'success' : 'gray'),
                TextColumn::make('created_at')->label('Создан')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options([
                    'open' => 'Открыт', 'closed' => 'Закрыт',
                ]),
                SelectFilter::make('type')->label('Тип')->options([
                    SupportTicket::TYPE_GENERAL => 'Общий',
                    SupportTicket::TYPE_PERCENT_REQUEST => 'Запрос %',
                    SupportTicket::TYPE_TRIAL_PREMIUM => 'Пробный премиум',
                    SupportTicket::TYPE_WITHDRAW_QUESTION => 'Вопрос по выводу',
                    SupportTicket::TYPE_HISTORY_REQUEST => 'История',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('reply')
                    ->label('Ответить')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->schema([
                        Textarea::make('reply')->label('Ответ пользователю')->required()->rows(3),
                    ])
                    ->action(function (SupportTicket $record, array $data): void {
                        app(Notifier::class)->notify($record->botUser, "📩 Ответ поддержки:\n\n{$data['reply']}");
                        $record->update(['status' => 'closed']);
                        AdminAudit::log('ticket_reply', $record, ['reply' => $data['reply']]);
                        Notification::make()->title('Ответ отправлен, тикет закрыт')->success()->send();
                    }),
                Action::make('close')
                    ->label('Закрыть')
                    ->icon('heroicon-o-check')
                    ->color('gray')
                    ->visible(fn (SupportTicket $r) => $r->status === 'open')
                    ->requiresConfirmation()
                    ->action(function (SupportTicket $record): void {
                        $record->update(['status' => 'closed']);
                        AdminAudit::log('ticket_close', $record);
                        Notification::make()->title('Тикет закрыт')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
