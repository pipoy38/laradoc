<?php
use App\Support\Settings;

it('stores, caches and encrypts settings', function () {
    $s = app(Settings::class);
    expect($s->get('site_name', 'fallback'))->toBe('fallback');
    $s->set('site_name', 'Docs');
    $s->set('mail_password', 'secret');
    expect(setting('site_name'))->toBe('Docs')
        ->and(\App\Models\Setting::where('key', 'mail_password')->value('value'))->not->toBe('secret')
        ->and($s->get('mail_password'))->toBe('secret');
    $s->setMany(['site_name' => 'Docs 2', 'site_tagline' => null]);
    expect(setting('site_name'))->toBe('Docs 2')->and(setting('site_tagline'))->toBeNull();
});
