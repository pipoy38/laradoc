<?php
namespace App\Http\Middleware;

use App\Support\MailConfig;
use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;

class ApplyMailSettings
{
    public function handle(Request $request, Closure $next)
    {
        MailConfig::apply(app(Settings::class));

        return $next($request);
    }
}
