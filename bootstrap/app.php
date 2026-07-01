<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // За Cloudflare + Caddy/nginx: доверяем прокси, чтобы Laravel видел https
        // (X-Forwarded-Proto) и корректно строил ссылки/куки/сессии.
        $middleware->trustProxies(at: '*');

        // Вебхуки внешних сервисов — без CSRF (защита своим секретом)
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
