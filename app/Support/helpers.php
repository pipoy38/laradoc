<?php
use App\Support\Settings;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}
if (! function_exists('admin_route')) {
    function admin_route(string $name, mixed $params = []): string
    {
        return route('admin.'.$name, $params);
    }
}
