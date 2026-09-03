<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;

it('builds nested category paths', function () {
    $root = Category::factory()->create(['slug' => 'guides']);
    $child = Category::factory()->create(['slug' => 'install', 'parent_id' => $root->id]);
    expect($child->path())->toBe('guides/install')
        ->and($child->ancestors()->pluck('slug')->all())->toBe(['guides'])
        ->and($root->descendantIds())->toBe([$root->id, $child->id]);
});

it('builds article paths and urls', function () {
    $cat = Category::factory()->create(['slug' => 'guides']);
    $a = Article::factory()->create(['slug' => 'hello', 'category_id' => $cat->id]);
    expect($a->path())->toBe('guides/hello')->and($a->url())->toBe(url('/guides/hello'));
});

it('scopes published articles', function () {
    Article::factory()->create(['status' => 'draft']);
    Article::factory()->create(['status' => 'published']);
    expect(Article::published()->count())->toBe(1);
});

it('gives users a role helper', function () {
    $u = User::factory()->create(['role' => 'editor']);
    expect($u->isAdmin())->toBeFalse();
    expect(User::factory()->admin()->create()->isAdmin())->toBeTrue();
});
