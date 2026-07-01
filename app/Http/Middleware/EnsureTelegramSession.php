<?php

namespace App\Http\Middleware;

use App\Models\BotUser;
use Closure;
use Illuminate\Http\Request;

/**
 * Гейт Mini App: пускает по сессии (mini_bot_user_id). В local-окружении — тест-байпас.
 * Иначе отдаёт boot-страницу, которая авторизуется по initData и перезагружает /app.
 */
class EnsureTelegramSession
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->session()->get('mini_bot_user_id');

        // Локальная разработка: входим тест-юзером без Telegram
        if (! $userId && app()->environment('local')) {
            $devId = (int) env('TELEGRAM_DEV_ID', 999000000);
            $user = BotUser::firstOrCreate(
                ['telegram_id' => $devId],
                ['first_name' => 'Local', 'last_name' => 'Tester'],
            );
            $request->session()->put('mini_bot_user_id', $user->id);
            $userId = $user->id;
        }

        $user = $userId ? BotUser::find($userId) : null;

        if (! $user) {
            $request->session()->forget('mini_bot_user_id');

            return response()->view('miniapp_boot');
        }

        if ($user->is_banned) {
            abort(403, 'Доступ ограничён.');
        }

        $request->attributes->set('botUser', $user);

        return $next($request);
    }
}
