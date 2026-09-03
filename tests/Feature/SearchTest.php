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

it('treats % as a literal character instead of a like wildcard', function () {
    Article::factory()->create(['title' => '100% uptime', 'slug' => 'uptime-percent', 'body_text' => 'reliability', 'category_id' => $this->a->category_id]);
    Article::factory()->create(['title' => '100 percent uptime', 'slug' => 'uptime-words', 'body_text' => 'reliability', 'category_id' => $this->a->category_id]);

    // "100%" must be matched literally: only the title that actually contains "100%" should
    // come back, not every row (which is what an unescaped "%" wildcard would return).
    $this->getJson('/api/search?q=100%25')->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', '100% uptime');

    // "%%" as the whole query must be treated as a literal (and harmless) two-character
    // string, not a wildcard that matches every row.
    $this->getJson('/api/search?q=%25%25')->assertOk()->assertJsonCount(0);
});

it('drops operator-only words instead of matching everything', function () {
    $this->getJson('/api/search?q=printer+%2B')->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Configure printers');
});

it('matches a 2-letter query word', function () {
    Article::factory()->create(['title' => 'Backups', 'slug' => 'backups', 'body_text' => 'db backup instructions', 'category_id' => $this->a->category_id]);
    $this->getJson('/api/search?q=db')->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Backups');
});
