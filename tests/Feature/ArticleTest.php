<?php

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Category;
use App\Models\PathRedirect;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->cat = Category::factory()->create(['slug' => 'guides']);
});

function articlePayload(array $over = []): array
{
    return array_merge(['title' => 'Hello World', 'slug' => '', 'category_id' => test()->cat->id, 'status' => 'published', 'body_html' => '<h2>Intro</h2><p>Text</p>', 'tags' => 'php, Laravel', 'change_note' => 'first'], $over);
}

it('creates an article with a revision, tags and derived fields', function () {
    $this->post('/admin/articles', articlePayload())->assertRedirect();
    $a = Article::firstWhere('slug', 'hello-world');
    expect($a)->not->toBeNull()
        ->and($a->body_text)->toBe('Intro Text')
        ->and($a->excerpt)->toBe('Intro Text')
        ->and($a->created_by)->toBe($this->user->id)
        ->and($a->published_at)->not->toBeNull()
        ->and($a->tags->pluck('slug')->sort()->values()->all())->toBe(['laravel', 'php'])
        ->and($a->revisions)->toHaveCount(1)
        ->and($a->revisions[0]->change_note)->toBe('first');
});

it('lists and filters articles', function () {
    Article::factory()->create(['title' => 'Alpha doc', 'category_id' => $this->cat->id]);
    Article::factory()->create(['title' => 'Beta doc', 'status' => 'draft', 'category_id' => $this->cat->id]);
    $this->get('/admin/articles?status=draft')->assertOk()->assertSee('Beta doc')->assertDontSee('Alpha doc');
    $this->get('/admin/articles?q=alpha')->assertOk()->assertSee('Alpha doc')->assertDontSee('Beta doc');
});

it('updates, records a redirect on slug change and stores a revision', function () {
    $a = Article::factory()->create(['slug' => 'old', 'category_id' => $this->cat->id]);
    $this->put("/admin/articles/{$a->id}", articlePayload(['slug' => 'new', 'title' => 'New title']))->assertRedirect("/admin/articles/{$a->id}/edit");
    expect($a->fresh()->slug)->toBe('new')
        ->and(PathRedirect::where('from_path', 'guides/old')->value('to_path'))->toBe('guides/new')
        ->and($a->fresh()->updated_by)->toBe($this->user->id)
        ->and(ArticleRevision::where('article_id', $a->id)->count())->toBe(1);
});

it('rejects duplicate slugs within a category', function () {
    Article::factory()->create(['slug' => 'dup', 'category_id' => $this->cat->id]);
    $this->post('/admin/articles', articlePayload(['slug' => 'dup']))->assertSessionHasErrors('slug');
});

it('rejects a slug that collides with a sub-category', function () {
    $child = Category::factory()->create(['parent_id' => $this->cat->id, 'slug' => 'dup']);
    $this->post('/admin/articles', articlePayload(['slug' => 'dup']))->assertSessionHasErrors('slug');
});

it('restores a revision', function () {
    $a = Article::factory()->create(['title' => 'v1', 'body_html' => '<p>one</p>', 'category_id' => $this->cat->id]);
    $this->put("/admin/articles/{$a->id}", articlePayload(['title' => 'v2', 'body_html' => '<p>two</p>', 'slug' => $a->slug]));
    $this->put("/admin/articles/{$a->id}", articlePayload(['title' => 'v3', 'body_html' => '<p>three</p>', 'slug' => $a->slug]));
    $rev = ArticleRevision::where('article_id', $a->id)->where('title', 'v2')->first();
    $this->get("/admin/articles/{$a->id}/revisions/{$rev->id}")->assertOk()->assertSee('two');
    $this->post("/admin/articles/{$a->id}/revisions/{$rev->id}/restore")->assertRedirect();
    $a->refresh();
    expect($a->title)->toBe('v2')->and($a->body_html)->toContain('two')
        ->and($a->revisions()->count())->toBe(3)->and($a->revisions()->first()->change_note)->toContain("Restored revision #{$rev->id}");
});

it('deletes an article with its revisions and tag links', function () {
    $a = Article::factory()->create(['category_id' => $this->cat->id]);
    $a->tags()->attach(Tag::factory()->create());
    ArticleRevision::create(['article_id' => $a->id, 'title' => 't', 'body_html' => '<p>x</p>']);
    $this->delete("/admin/articles/{$a->id}")->assertRedirect('/admin/articles');
    expect(Article::count())->toBe(0)->and(ArticleRevision::count())->toBe(0)->and(DB::table('article_tag')->count())->toBe(0);
});
