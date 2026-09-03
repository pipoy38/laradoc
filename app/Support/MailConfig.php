<?php
namespace App\Support;

use Illuminate\Support\Facades\Schema;

class MailConfig
{
    /**
     * Apply the stored mail settings to the runtime `mail` config. Called both from
     * AppServiceProvider::boot() (console/queue contexts, once per app boot) and from
     * ApplyMailSettings middleware (once per web request), since the test client reuses
     * the same booted app instance across requests within a test.
     */
    public static function apply(Settings $s): void
    {
        try {
            if (! Schema::hasTable('settings')) { return; }
            $mailer = $s->get('mail_mailer', 'log');
            config(['mail.default' => $mailer]);
            if ($mailer === 'smtp') {
                $encryptionSetting = $s->get('mail_encryption', 'tls');
                config(['mail.mailers.smtp.host' => $s->get('mail_host'), 'mail.mailers.smtp.port' => (int) $s->get('mail_port', 587),
                    'mail.mailers.smtp.username' => $s->get('mail_username'), 'mail.mailers.smtp.password' => $s->get('mail_password'),
                    'mail.mailers.smtp.encryption' => $encryptionSetting === 'tls' ? 'tls' : null,
                    'mail.mailers.smtp.scheme' => $encryptionSetting === 'ssl' ? 'smtps' : null]);
            }
            if ($from = $s->get('mail_from_address')) { config(['mail.from.address' => $from, 'mail.from.name' => $s->get('mail_from_name', $s->get('site_name'))]); }
        } catch (\Throwable) { /* DB not ready */ }
    }
}
