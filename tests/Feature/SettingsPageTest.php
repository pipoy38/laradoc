<?php
use App\Mail\TestMail;
use App\Models\Media;
use App\Models\User;
use App\Support\AdminPrefix;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

it('saves general and branding settings', function () {
    $logo = Media::factory()->create();
    $this->put('/admin/settings', ['section' => 'general', 'site_name' => 'ERP Docs', 'site_tagline' => 'How it works', 'home_intro' => '<p>Welcome</p>', 'footer_text' => 'Made in PH'])->assertRedirect();
    $this->put('/admin/settings', ['section' => 'branding', 'logo_media_id' => $logo->id, 'favicon_media_id' => ''])->assertRedirect();
    expect(setting('site_name'))->toBe('ERP Docs')->and(setting('logo_media_id'))->toBe((string) $logo->id)->and(setting('favicon_media_id'))->toBeNull();
    $this->get('/')->assertSee('ERP Docs')->assertSee('Made in PH')->assertSee($logo->url(), false);
});

it('saves email settings, applies them to mail config and sends a test', function () {
    Mail::fake();
    $this->put('/admin/settings', ['section' => 'email', 'mail_mailer' => 'smtp', 'mail_host' => 'smtp.example.com', 'mail_port' => 465, 'mail_username' => 'u', 'mail_password' => 'p',
        'mail_encryption' => 'ssl', 'mail_from_address' => 'docs@example.com', 'mail_from_name' => 'Docs'])->assertRedirect();
    $this->get('/admin/settings'); // triggers provider on a fresh request
    expect(config('mail.default'))->toBe('smtp')->and(config('mail.mailers.smtp.host'))->toBe('smtp.example.com')->and(config('mail.from.address'))->toBe('docs@example.com');
    $this->post('/admin/settings/test-mail')->assertSessionHas('ok');
    Mail::assertSent(TestMail::class, fn ($m) => $m->hasTo(auth()->user()->email));
});

it('supports explicitly disabling smtp encryption', function () {
    $this->put('/admin/settings', ['section' => 'email', 'mail_mailer' => 'smtp', 'mail_host' => 'h', 'mail_port' => 587, 'mail_username' => 'u', 'mail_password' => 'p', 'mail_encryption' => 'none', 'mail_from_address' => 'a@b.c', 'mail_from_name' => 'X'])->assertRedirect();
    expect(setting('mail_encryption'))->toBe('none');
    $this->get('/admin/settings') // triggers provider on a fresh request
        ->assertSee('value="none" selected', false);
    expect(config('mail.mailers.smtp.encryption'))->toBeNull();
});

it('keeps the stored mail password when the field is left blank', function () {
    app(\App\Support\Settings::class)->set('mail_password', 'keep-me');
    $this->put('/admin/settings', ['section' => 'email', 'mail_mailer' => 'smtp', 'mail_host' => 'h', 'mail_port' => 587, 'mail_username' => 'u', 'mail_password' => '', 'mail_encryption' => 'tls', 'mail_from_address' => 'a@b.c', 'mail_from_name' => 'X']);
    expect(setting('mail_password'))->toBe('keep-me');
});

it('changes the admin prefix with validation', function () {
    $this->put('/admin/settings', ['section' => 'admin_url', 'admin_prefix' => 'search'])->assertSessionHasErrors('admin_prefix');
    $this->put('/admin/settings', ['section' => 'admin_url', 'admin_prefix' => 'ops'])->assertRedirectContains('/ops/settings');
    expect(AdminPrefix::current())->toBe('ops');
});
