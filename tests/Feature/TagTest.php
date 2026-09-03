<?php
use App\Models\Tag;
use App\Models\User;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('manages tags', function () {
    $this->post('/admin/tags', ['name' => 'Getting Started'])->assertRedirect('/admin/tags');
    $t = Tag::firstWhere('slug', 'getting-started');
    $this->put("/admin/tags/{$t->id}", ['name' => 'Start'])->assertRedirect();
    expect($t->fresh()->slug)->toBe('start');
    $this->get('/admin/tags')->assertOk()->assertSee('Start');
    $this->delete("/admin/tags/{$t->id}")->assertRedirect();
    expect(Tag::count())->toBe(0);
});
