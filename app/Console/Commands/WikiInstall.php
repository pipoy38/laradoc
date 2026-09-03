<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\User;
use App\Support\AdminPrefix;
use App\Support\Settings;
use Database\Seeders\SampleContentSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class WikiInstall extends Command
{
    protected $signature = 'wiki:install {--name=} {--username=} {--email=} {--password=}';

    protected $description = 'Run migrations, create the first admin user, seed sample content and link storage';

    public function handle(Settings $settings): int
    {
        Artisan::call('migrate', ['--force' => true], $this->output);
        if (User::count() === 0) {
            $name = $this->option('name') ?: $this->adminEnv('ADMIN_NAME') ?: $this->ask('Admin display name', 'Administrator');
            $username = $this->option('username') ?: $this->adminEnv('ADMIN_USERNAME') ?: $this->ask('Admin username', 'admin');
            $email = $this->option('email') ?: $this->adminEnv('ADMIN_EMAIL') ?: $this->ask('Admin email');
            $password = $this->option('password') ?: $this->adminEnv('ADMIN_PASSWORD') ?: $this->secret('Admin password (min 8 chars)');
            if (! $email || strlen((string) $password) < 8) {
                $this->error('Email and a password of at least 8 characters are required.');

                return self::FAILURE;
            }
            User::create(['name' => $name, 'username' => $username, 'email' => $email, 'password' => $password, 'role' => 'admin']);
            $this->info("Admin user '$username' created.");
        } else {
            $this->line('Users already exist; skipping admin creation.');
        }
        if (! $settings->get('site_name_set')) {
            $settings->setMany(['site_name' => 'Laradoc', 'site_tagline' => 'Documentation', 'site_name_set' => '1']);
        }
        if (Article::count() === 0) {
            Artisan::call('db:seed', ['--class' => SampleContentSeeder::class, '--force' => true]);
            $this->info('Sample content seeded.');
        }
        try {
            Artisan::call('storage:link');
        } catch (\Throwable) {
        }
        $this->info('Done. Admin URL: /'.AdminPrefix::current());

        return self::SUCCESS;
    }

    /**
     * env() reads from the cached config once `config:cache` has run, at which point
     * environment lookups outside the original config files return null. Fall back to
     * getenv()/$_ENV so ADMIN_* still works non-interactively after config:cache.
     */
    private function adminEnv(string $key): ?string
    {
        return env($key) ?: (getenv($key) ?: null) ?: ($_ENV[$key] ?? null);
    }
}
