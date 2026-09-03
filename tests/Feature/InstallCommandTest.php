<?php

use App\Models\Article;
use App\Models\User;

it('installs the first admin and sample content non-interactively', function () {
    $this->artisan('wiki:install', ['--name' => 'Root', '--username' => 'root', '--email' => 'root@example.com', '--password' => 'rootpass123'])->assertExitCode(0);
    $u = User::firstWhere('username', 'root');
    expect($u->role)->toBe('admin')->and(Article::count())->toBe(1)->and(setting('site_name'))->toBe('Laradoc');
    $this->artisan('wiki:install', ['--name' => 'Root', '--username' => 'root', '--email' => 'root@example.com', '--password' => 'rootpass123'])->assertExitCode(0);
    expect(User::count())->toBe(1)->and(Article::count())->toBe(1);
});

it('reads ADMIN_* from the environment when no options are given (still works after config:cache)', function () {
    putenv('ADMIN_EMAIL=env@example.com');
    putenv('ADMIN_PASSWORD=envpass123');
    putenv('ADMIN_USERNAME=envadmin');
    putenv('ADMIN_NAME=Env Admin');
    try {
        $this->artisan('wiki:install')->assertExitCode(0);
        $u = User::firstWhere('username', 'envadmin');
        expect($u)->not->toBeNull()->and($u->email)->toBe('env@example.com')->and($u->name)->toBe('Env Admin')->and($u->role)->toBe('admin');
    } finally {
        putenv('ADMIN_EMAIL');
        putenv('ADMIN_PASSWORD');
        putenv('ADMIN_USERNAME');
        putenv('ADMIN_NAME');
    }
});
