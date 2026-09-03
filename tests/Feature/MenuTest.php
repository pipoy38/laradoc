<?php

use App\Models\MenuItem;
use App\Models\User;

beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

it('manages menu items and reorders them', function () {
    $this->post('/admin/menu', ['label' => 'GitHub', 'url' => 'https://github.com/pao2pao16', 'new_tab' => 1])->assertRedirect('/admin/menu');
    $this->post('/admin/menu', ['label' => 'App', 'url' => 'https://erp.example.com'])->assertRedirect();
    [$a, $b] = MenuItem::orderBy('sort_order')->get();
    expect($a->label)->toBe('GitHub')->and($a->new_tab)->toBeTrue();
    $this->post("/admin/menu/{$b->id}/move", ['dir' => 'up']);
    expect(MenuItem::orderBy('sort_order')->first()->label)->toBe('App');
    $this->put("/admin/menu/{$a->id}", ['label' => 'Code', 'url' => 'https://github.com'])->assertRedirect();
    expect($a->fresh()->label)->toBe('Code')->and($a->fresh()->new_tab)->toBeFalse();
    $this->delete("/admin/menu/{$a->id}")->assertRedirect();
    expect(MenuItem::count())->toBe(1);
    $this->get('/')->assertSee('erp.example.com');
});

it('is admin only', fn () => $this->actingAs(User::factory()->create())->get('/admin/menu')->assertForbidden());

it('rejects unsafe url schemes but allows safe ones', function () {
    $this->post('/admin/menu', ['label' => 'Bad', 'url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
    $this->post('/admin/menu', ['label' => 'Path', 'url' => '/docs'])->assertSessionDoesntHaveErrors('url');
    $this->post('/admin/menu', ['label' => 'Site', 'url' => 'https://example.com'])->assertSessionDoesntHaveErrors('url');
});
