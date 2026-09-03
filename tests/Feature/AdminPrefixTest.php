<?php
use App\Support\AdminPrefix;
use App\Support\Settings;
use Illuminate\Support\Facades\Route;

it('defaults to admin and reads the setting', function () {
    expect(AdminPrefix::current())->toBe('admin');
    app(Settings::class)->set('admin_prefix', 'ops');
    expect(AdminPrefix::current())->toBe('ops');
});

it('validates prefixes', function () {
    expect(AdminPrefix::isValid('ops-panel'))->toBeTrue()
        ->and(AdminPrefix::isValid('search'))->toBeFalse()
        ->and(AdminPrefix::isValid('a'))->toBeFalse()
        ->and(AdminPrefix::isValid('Bad Prefix'))->toBeFalse();
});

it('serves the admin under a custom prefix', function () {
    Route::middleware('web')->prefix('ops')->name('admin.')->group(base_path('routes/admin.php'));
    $this->get('/ops/login')->assertOk();
});

it('shows and resets the prefix from the console', function () {
    app(Settings::class)->set('admin_prefix', 'ops');
    $this->artisan('wiki:admin-prefix')->expectsOutputToContain('ops')->assertExitCode(0);
    $this->artisan('wiki:admin-prefix', ['action' => 'reset'])->assertExitCode(0);
    expect(AdminPrefix::current())->toBe('admin');
});
