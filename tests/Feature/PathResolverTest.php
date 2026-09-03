<?php
use App\Models\Article;
use App\Models\Category;
use App\Support\PathResolver;

beforeEach(function () {
    $this->root = Category::factory()->create(['slug' => 'guides']);
    $this->child = Category::factory()->create(['slug' => 'install', 'parent_id' => $this->root->id]);
    $this->article = Article::factory()->create(['slug' => 'linux', 'category_id' => $this->child->id]);
    Article::factory()->create(['slug' => 'draft', 'category_id' => $this->child->id, 'status' => 'draft']);
});

it('resolves categories and articles by path', function () {
    $r = app(PathResolver::class);
    expect($r->resolve('guides')['category']->id)->toBe($this->root->id)
        ->and($r->resolve('guides/install')['category']->id)->toBe($this->child->id)
        ->and($r->resolve('guides/install/linux')['article']->id)->toBe($this->article->id)
        ->and($r->resolve('guides/install/draft'))->toBeNull()
        ->and($r->resolve('guides/install/draft', publishedOnly: false)['type'])->toBe('article')
        ->and($r->resolve('guides/nope/linux'))->toBeNull()
        ->and($r->resolve(''))->toBeNull();
});

it('builds a published-only tree', function () {
    $tree = app(PathResolver::class)->tree();
    expect($tree)->toHaveCount(1)
        ->and($tree[0]->children)->toHaveCount(1)
        ->and($tree[0]->children[0]->articles->pluck('slug')->all())->toBe(['linux']);
});
