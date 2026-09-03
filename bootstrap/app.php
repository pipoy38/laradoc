<?php

use App\Http\Middleware\ApplyMailSettings;
use App\Http\Middleware\EnsureRole;
use App\Support\AdminPrefix;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->prefix(AdminPrefix::current())->name('admin.')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
        $middleware->web(append: [ApplyMailSettings::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {})->create();
