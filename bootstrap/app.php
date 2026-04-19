<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('app:envoyer-relances')->dailyAt('09:00');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.jwt' => \App\Http\Middleware\JwtAuth::class,
            'role'     => \App\Http\Middleware\RoleMiddleware::class,
            'shop'     => \App\Http\Middleware\ShopMiddleware::class,
            'audit'    => \App\Http\Middleware\AuditMiddleware::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\AuditMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// Hostinger: public_html est séparé de app_laravel
if ($publicPath = env('PUBLIC_PATH')) {
    $app->usePublicPath(base_path($publicPath));
}

return $app;
