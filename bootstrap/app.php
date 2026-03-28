<?php

use App\Console\Commands\PenaltyAutomator;
use App\Http\Middleware\AuditTrailMiddleware;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'audit'       => AuditTrailMiddleware::class,
            'active.user' => EnsureUserIsActive::class,
        ]);
        $middleware->appendToGroup('web', AuditTrailMiddleware::class);
        $middleware->appendToGroup('web', EnsureUserIsActive::class);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command(PenaltyAutomator::class)
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/penalty-automator.log'), true);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
