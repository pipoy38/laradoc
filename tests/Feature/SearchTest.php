<?php
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;

beforeEach(function () {
    $c = Category::factory()->create(['slug' => 'g']);
    $this->a = Article::factory()->create(['title' => 'Configure printers', 'slug' => 'printers', 'body_html' => '<p>Thermal receipt printer setup</p>', 'body_text' => 'Thermal receipt printer setup', 'category_id' => $c->id]);
    Article::factory()->create(['title' => 'Hidden printers', 'slug' => 'hidden', 'status' => 'draft', 'body_text' => 'printer', 'category_id' => $c->id]);
    Article::factory()->create(['title' => 'Unrelated', 'slug' => 'u', 'body_text' => 'nothing here', 'category_id' => $c->id]);
});

it('returns published matches as json', function () {
    $this->getJson('/api/search?q=printer')->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Configure printers')->assertJsonPath('0.url', $this->a->url());
    $this->getJson('/api/search?q=p')->assertOk()->assertJsonCount(0);
});

it('renders a results page', function () {
    $this->get('/search?q=receipt')->assertOk()->assertSee('Configure printers')->assertDontSee('Hidden printers')->assertSee('1 result');
    $this->get('/search?q=zzzz')->assertOk()->assertSee('No results');
});

it('renders tag pages', function () {
    $t = Tag::factory()->create(['name' => 'Hardware', 'slug' => 'hardware']);
    $this->a->tags()->attach($t);
    $this->get('/tags/hardware')->assertOk()->assertSee('Hardware')->assertSee('Configure printers');
    $this->get('/tags/nope')->assertNotFound();
});
