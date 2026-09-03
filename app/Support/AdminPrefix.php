<?php

namespace App\Support;

class AdminPrefix
{
    public const DEFAULT = 'admin';

    public const RESERVED = ['search', 'tags', 'api', 'sitemap.xml', 'robots.txt', 'storage', 'build', 'vendor', 'login', 'logout', 'up'];

    public static function current(): string
    {
        try {
            $value = app(Settings::class)->get('admin_prefix', self::DEFAULT);

            return self::isValid($value) ? $value : self::DEFAULT;
        } catch (\Throwable) {
            return self::DEFAULT; // no DB yet (fresh install, route:cache before migrate)
        }
    }

    public static function reserved(): array
    {
        return self::RESERVED;
    }

    public static function isValid(?string $value): bool
    {
        return is_string($value) && preg_match('/^[a-z0-9-]{2,32}$/', $value) === 1 && ! in_array($value, self::RESERVED, true);
    }
}
