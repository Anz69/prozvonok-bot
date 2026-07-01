<?php

namespace App\Http\Middleware;

use App\Models\BotUser;
use App\Models\Geo;
use App\Models\Setting;
use App\Telegram\Support\Screens;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'miniapp';

    public function share(Request $request): array
    {
        /** @var BotUser|null $user */
        $user = $request->attributes->get('botUser');

        return [
            ...parent::share($request),
            'user' => $user ? $this->userPayload($user) : null,
            'geos' => Geo::active()->orderBy('sort')->get()->map(fn (Geo $g) => [
                'code' => $g->code,
                'flag' => $g->flag,
                'name' => $g->name,
                'price' => (float) $g->price_per_1000,
            ]),
            'settings' => [
                'free_numbers' => (int) Setting::get('free_numbers', 200),
                'max_numbers' => (int) Setting::get('max_numbers', 100000),
                'min_deposit' => (float) Setting::get('min_deposit', 10),
                'min_withdraw' => (float) Setting::get('min_withdraw', 50),
                'wh_start' => (int) Setting::get('working_hours_start', 9),
                'wh_end' => (int) Setting::get('working_hours_end', 21),
                'avg_minutes' => (int) Setting::get('avg_processing_minutes', 15),
                'support_url' => (string) Setting::get('support_url', ''),
                'channel_url' => (string) Setting::get('channel_url', ''),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    private function userPayload(BotUser $user): array
    {
        return [
            'id' => $user->telegram_id,
            'name' => $user->displayName(),
            'first_name' => $user->first_name,
            'username' => $user->username,
            'deposit' => (float) $user->deposit_balance,
            'referral_balance' => (float) $user->referral_balance,
            'total_deposited' => (float) $user->total_deposited,
            'referral_percent' => (int) $user->referral_percent,
            'discount' => (int) $user->check_discount,
            'premium' => $user->hasActivePremium() ? $user->premium_tier : null,
            'premium_until' => $user->hasActivePremium() ? $user->premium_until?->format('d.m.Y') : null,
            'numbers_checked' => (int) $user->numbers_checked,
            'numbers_answered' => (int) $user->numbers_answered,
            'numbers_failed' => (int) $user->numbers_failed,
            'referral_link' => $user->referralLink(),
            'referrals' => $user->referralsCount(),
            'can_withdraw' => $user->canWithdraw(),
            'reg_date' => $user->created_at?->format('d.m.Y'),
        ];
    }
}
