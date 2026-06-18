<?php

namespace App\Services;

use App\Models\RequiredChannel;
use App\Models\Setting;
use SergiX44\Nutgram\Nutgram;

/**
 * Онбординг (раздел 3.1): обязательная подписка на канал и анти-бот капча.
 */
class OnboardingService
{
    private const SUBSCRIBED_STATUSES = ['creator', 'administrator', 'member', 'restricted'];

    /**
     * @return \Illuminate\Support\Collection<int, RequiredChannel>
     */
    public function requiredChannels()
    {
        if (! Setting::get('subscription_required', true)) {
            return collect();
        }

        return RequiredChannel::active()->get();
    }

    /**
     * Подписан ли пользователь на все активные обязательные каналы (реальная проверка через Telegram API).
     */
    public function isSubscribed(Nutgram $bot, int $userId): bool
    {
        $channels = $this->requiredChannels();
        if ($channels->isEmpty()) {
            return true;
        }

        foreach ($channels as $channel) {
            try {
                $member = $bot->getChatMember($channel->chat_id, $userId);
                if (! in_array($member?->status?->value ?? $member?->status, self::SUBSCRIBED_STATUSES, true)) {
                    return false;
                }
            } catch (\Throwable) {
                // если не смогли проверить (бот не админ канала и т.п.) — не блокируем жёстко
                return false;
            }
        }

        return true;
    }

    /**
     * Сгенерировать капчу: целевой эмодзи и перемешанный набор вариантов.
     *
     * @return array{target: string, options: list<string>}
     */
    public function makeCaptcha(): array
    {
        $target = (string) Setting::get('captcha_target', '🍍');
        $pool = (array) Setting::get('captcha_emojis', ['🍍', '🍎', '🍌', '🍇', '🍒', '🍓']);

        // гарантируем присутствие целевого эмодзи и до 6 вариантов
        $options = array_values(array_unique(array_merge([$target], $pool)));
        shuffle($options);
        $options = array_slice($options, 0, 6);
        if (! in_array($target, $options, true)) {
            $options[array_rand($options)] = $target;
        }
        shuffle($options);

        return ['target' => $target, 'options' => $options];
    }
}
