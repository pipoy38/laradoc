<?php

use App\Models\Article;
use App\Models\Category;

it('lists published pages in the sitemap and serves robots', function () {
    $c = Category::factory()->create(['slug' => 'g']);
    Article::factory()->create(['slug' => 'yes', 'category_id' => $c->id]);
    Article::factory()->create(['slug' => 'no', 'status' => 'draft', 'category_id' => $c->id]);
    Article::factory()->create(['slug' => 'hidden', 'noindex' => true, 'category_id' => $c->id]);
    $this->get('/sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml')
        ->assertSee(url('/g/yes'))->assertSee(url('/g'))->assertDontSee(url('/g/no'))->assertDontSee(url('/g/hidden'));
    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.url('/sitemap.xml'))->assertSee('Disallow: /admin');
});
