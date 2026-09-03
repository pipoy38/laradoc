<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('views'));

        ResetPassword::createUrlUsing(fn ($user, string $token) => route('admin.password.reset', ['token' => $token, 'email' => $user->email]));
    }
}
