<?php
use App\Models\Article;
use App\Models\Category;
use App\Models\PathRedirect;
use App\Support\Settings;

beforeEach(function () {
    app(Settings::class)->set('site_name', 'ERP Docs');
    $this->root = Category::factory()->create(['name' => 'Guides', 'slug' => 'guides']);
    $this->child = Category::factory()->create(['name' => 'Install', 'slug' => 'install', 'parent_id' => $this->root->id]);
    $this->a1 = Article::factory()->create(['title' => 'Linux setup', 'slug' => 'linux', 'category_id' => $this->child->id, 'body_html' => '<h2>Step one</h2><p>Body</p><pre><code class="language-bash">ls</code></pre>', 'meta_description' => 'Install on Linux', 'sort_order' => 0]);
    $this->a2 = Article::factory()->create(['title' => 'Windows setup', 'slug' => 'windows', 'category_id' => $this->child->id, 'sort_order' => 1]);
    $this->draft = Article::factory()->create(['title' => 'Secret', 'slug' => 'secret', 'category_id' => $this->child->id, 'status' => 'draft']);
});

it('renders the home page with categories and recent articles', function () {
    app(Settings::class)->set('home_intro', '<p>Welcome to the docs</p>');
    $this->get('/')->assertOk()->assertSee('Welcome to the docs')->assertSee('Guides')->assertSee('Linux setup')->assertDontSee('Secret')
        ->assertSee('<title>ERP Docs</title>', false);
});

it('renders a category page', function () {
    $this->get('/guides')->assertOk()->assertSee('Install')->assertSee('/guides/install', false);
    $this->get('/guides/install')->assertOk()->assertSee('Linux setup')->assertSee('Windows setup')->assertDontSee('Secret');
});

it('renders an article with breadcrumbs, toc, prev/next, sidebar and seo', function () {
    $r = $this->get('/guides/install/linux')->assertOk();
    $r->assertSee('<title>Linux setup · ERP Docs</title>', false)
      ->assertSee('<meta name="description" content="Install on Linux">', false)
      ->assertSee('<link rel="canonical" href="'.url('/guides/install/linux').'">', false)
      ->assertSee('property="og:title"', false)
      ->assertSee('"@type":"TechArticle"', false)
      ->assertSee('<h2 id="step-one">', false)
      ->assertSee('href="#step-one"', false)
      ->assertSee('Windows setup')            // next link
      ->assertSee('class="tree', false)
      ->assertSee('aria-current="page"', false)
      ->assertSee('Guides');                   // breadcrumb
    $this->get('/guides/install/windows')->assertOk()->assertSee('Linux setup'); // prev link
});

it('hides drafts and noindex articles correctly', function () {
    $this->get('/guides/install/secret')->assertNotFound();
    $this->a1->update(['noindex' => true, 'canonical_url' => 'https://elsewhere.example/x']);
    $this->get('/guides/install/linux')->assertSee('<meta name="robots" content="noindex', false)->assertSee('href="https://elsewhere.example/x"', false);
});

it('follows path redirects and 404s otherwise', function () {
    PathRedirect::create(['from_path' => 'old/path', 'to_path' => 'guides/install/linux']);
    $this->get('/old/path')->assertRedirect(url('/guides/install/linux'))->assertStatus(301);
    expect(PathRedirect::first()->hits)->toBe(1);
    $this->get('/nope/nothing')->assertNotFound()->assertSee('Search');
});
