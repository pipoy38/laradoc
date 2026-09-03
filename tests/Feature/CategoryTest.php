<?php
use App\Models\Article;
use App\Models\Category;
use App\Models\User;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('lists, creates, edits and deletes categories', function () {
    $this->get('/admin/categories')->assertOk();
    $this->post('/admin/categories', ['name' => 'Guides', 'slug' => '', 'parent_id' => null])->assertRedirect('/admin/categories');
    $c = Category::firstWhere('slug', 'guides');
    expect($c)->not->toBeNull();
    $this->put("/admin/categories/{$c->id}", ['name' => 'Guides 2', 'slug' => 'guides-2', 'parent_id' => null])->assertRedirect();
    expect($c->fresh()->slug)->toBe('guides-2');
    $this->delete("/admin/categories/{$c->id}")->assertRedirect();
    expect(Category::count())->toBe(0);
});

it('refuses to delete a category with children or articles', function () {
    $c = Category::factory()->create();
    Article::factory()->create(['category_id' => $c->id]);
    $this->delete("/admin/categories/{$c->id}")->assertSessionHas('err');
    expect(Category::count())->toBe(1);
});

it('refuses cyclic parents and duplicate sibling slugs', function () {
    $a = Category::factory()->create(['slug' => 'a']);
    $b = Category::factory()->create(['slug' => 'b', 'parent_id' => $a->id]);
    $this->put("/admin/categories/{$a->id}", ['name' => 'A', 'slug' => 'a', 'parent_id' => $b->id])->assertSessionHasErrors('parent_id');
    $this->post('/admin/categories', ['name' => 'A again', 'slug' => 'a', 'parent_id' => null])->assertSessionHasErrors('slug');
});

it('records a redirect when a category slug changes', function () {
    $a = Category::factory()->create(['slug' => 'old']);
    Article::factory()->create(['slug' => 'art', 'category_id' => $a->id]);
    $this->put("/admin/categories/{$a->id}", ['name' => 'Old', 'slug' => 'new', 'parent_id' => null]);
    expect(\App\Models\PathRedirect::where('from_path', 'old/art')->value('to_path'))->toBe('new/art')
        ->and(\App\Models\PathRedirect::where('from_path', 'old')->value('to_path'))->toBe('new');
});

it('moves categories up and down', function () {
    $a = Category::factory()->create(['sort_order' => 0]);
    $b = Category::factory()->create(['sort_order' => 1]);
    $this->post("/admin/categories/{$b->id}/move", ['dir' => 'up'])->assertRedirect();
    expect($b->fresh()->sort_order)->toBe(0)->and($a->fresh()->sort_order)->toBe(1);
});
