<?php
namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Settings
{
    public const CACHE_KEY = 'laradoc.settings';
    public const ENCRYPTED = ['mail_password'];
    public const DEFAULTS = ['site_name' => 'Laradoc', 'admin_prefix' => 'admin', 'mail_mailer' => 'log', 'mail_port' => '587', 'mail_encryption' => 'tls'];

    private ?array $loaded = null;

    public function all(): array
    {
        return $this->loaded ??= Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;
        if ($value === null || $value === '') { return $default ?? (self::DEFAULTS[$key] ?? null); }
        if (in_array($key, self::ENCRYPTED, true)) { try { return Crypt::decryptString($value); } catch (\Throwable) { return null; } }
        return $value;
    }

    public function set(string $key, ?string $value): void { $this->setMany([$key => $value]); }

    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            if ($value !== null && in_array($key, self::ENCRYPTED, true)) { $value = Crypt::encryptString($value); }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->flush();
    }

    public function flush(): void { $this->loaded = null; Cache::forget(self::CACHE_KEY); }
}
