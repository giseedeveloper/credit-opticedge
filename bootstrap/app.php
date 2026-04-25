<?php

use App\Console\Commands\PenaltyAutomator;
use App\Http\Middleware\AuditTrailMiddleware;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'audit' => AuditTrailMiddleware::class,
            'active.user' => EnsureUserIsActive::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
        $middleware->appendToGroup('web', AuditTrailMiddleware::class);
        $middleware->appendToGroup('web', EnsureUserIsActive::class);
        $middleware->prependToGroup('api', HandleCors::class);
        $middleware->appendToGroup('api', EnsureUserIsActive::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(PenaltyAutomator::class)
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/penalty-automator.log'), true);

        $schedule->command('app:sync-device-catalog')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/device-catalog-sync.log'), true);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
