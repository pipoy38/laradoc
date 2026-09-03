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
