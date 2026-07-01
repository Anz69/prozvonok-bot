<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Управление FAQ мини-аппа. Хранится в Setting('faq_items') как JSON [{q, a}].
 * Мини-апп читает тот же ключ на странице /app/faq.
 */
class ManageFaq extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'FAQ (мини-апп)';

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $title = 'FAQ мини-аппа';

    protected string $view = 'filament.pages.manage-faq';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        // Контентная настройка — только для админов (как и Настройки).
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $items = Setting::get('faq_items', []);
        $this->form->fill(['items' => is_array($items) ? array_values($items) : []]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('items')
                    ->label('Вопросы и ответы')
                    ->schema([
                        TextInput::make('q')
                            ->label('Вопрос')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),
                        Textarea::make('a')
                            ->label('Ответ')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->reorderable()
                    ->collapsible()
                    ->cloneable()
                    ->defaultItems(0)
                    ->itemLabel(fn (array $state): ?string => $state['q'] ?? null)
                    ->addActionLabel('Добавить вопрос'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $items = collect($this->form->getState()['items'] ?? [])
            ->map(fn ($i) => ['q' => trim((string) ($i['q'] ?? '')), 'a' => trim((string) ($i['a'] ?? ''))])
            ->filter(fn ($i) => $i['q'] !== '' && $i['a'] !== '')
            ->values()
            ->all();

        Setting::put('faq_items', $items, 'json', 'content');

        Notification::make()->success()->title('FAQ сохранён')->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Сохранить')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
