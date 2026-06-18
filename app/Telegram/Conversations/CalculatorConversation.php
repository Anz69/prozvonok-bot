<?php

namespace App\Telegram\Conversations;

use App\Models\BotText;
use App\Models\Geo;
use App\Services\PricingService;
use App\Telegram\Support\Screen;
use App\Telegram\Support\Screens;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\UpdateType;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Калькулятор (раздел 3.9) в режиме одного экрана: ГЕО → количество → стоимость.
 */
class CalculatorConversation extends BotConversation
{
    public ?string $geoCode = null;

    private function geoKeyboard(): InlineKeyboardMarkup
    {
        $kb = GeoKeyboard::make('calc:geo:');
        $kb->addRow(...Screen::navRow('home'));

        return $kb;
    }

    public function start(Nutgram $bot): void
    {
        $this->screen($bot, BotText::render('calculator', ['prices' => Screens::priceList()]), $this->geoKeyboard());
        $this->next('chooseGeo');
    }

    public function chooseGeo(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        $code = GeoKeyboard::extract($bot, 'calc:geo:');
        if ($code === null) {
            $bot->answerCallbackQuery();

            return;
        }

        $this->geoCode = $code;
        $bot->answerCallbackQuery();
        $this->screen(
            $bot,
            BotText::render('calculator_ask_count'),
            InlineKeyboardMarkup::make()->addRow(...Screen::navRow('home')),
        );
        $this->next('compute');
    }

    public function compute(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        $count = (int) preg_replace('/\D/', '', (string) $bot->message()?->text);
        if ($count <= 0) {
            $this->screen(
                $bot,
                BotText::render('calculator_ask_count'),
                InlineKeyboardMarkup::make()->addRow(...Screen::navRow('home')),
            );
            $this->next('compute');

            return;
        }

        $geo = Geo::where('code', $this->geoCode)->first();
        if (! $geo) {
            $this->end();

            return;
        }

        $quote = app(PricingService::class)->quote($count, $geo);
        $this->screen($bot, BotText::render('calculator_result', [
            'geo' => $geo->flag . ' ' . $geo->code,
            'count' => $count,
            'cost' => Screens::money($quote['cost']),
            'price' => Screens::money($geo->price_per_1000),
        ]), $this->geoKeyboard());

        // новое число → пересчёт; выбор ГЕО → смена ГЕО
        $this->next('compute', UpdateType::MESSAGE);
        $this->next('chooseGeo', UpdateType::CALLBACK_QUERY);
    }
}
