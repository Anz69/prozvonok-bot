<?php

namespace App\Providers;

use App\Services\Usdt\FakeUsdtWatcher;
use App\Services\Usdt\TronscanWatcher;
use App\Services\Usdt\UsdtWatcher;
use App\Services\Zvonok\FakeZvonokClient;
use App\Services\Zvonok\HttpZvonokClient;
use App\Services\Zvonok\ZvonokClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Драйвер приёма USDT (раздел 4 / 3.4): fake — локаль/тесты, tronscan — прод.
        $this->app->bind(UsdtWatcher::class, fn () => match (config('dozvon.usdt.provider')) {
            'tronscan' => new TronscanWatcher(),
            default => new FakeUsdtWatcher(),
        });

        // Драйвер обзвона Звонок.com: fake — локаль/тесты, http — прод.
        $this->app->bind(ZvonokClient::class, fn ($app) => match (config('dozvon.zvonok.driver')) {
            'http' => new HttpZvonokClient(),
            default => new FakeZvonokClient(),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
