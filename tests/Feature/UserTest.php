<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () { $this->admin = User::factory()->admin()->create(); $this->actingAs($this->admin); });

it('creates, edits and deletes users', function () {
    $this->post('/admin/users', ['name' => 'Bob', 'username' => 'bob', 'email' => 'bob@x.io', 'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'editor'])->assertRedirect('/admin/users');
    $u = User::firstWhere('username', 'bob');
    expect(Hash::check('password123', $u->password))->toBeTrue()->and($u->role)->toBe('editor');
    $this->put("/admin/users/{$u->id}", ['name' => 'Bobby', 'username' => 'bob', 'email' => 'bob@x.io', 'password' => '', 'role' => 'admin'])->assertRedirect();
    expect($u->fresh()->name)->toBe('Bobby')->and($u->fresh()->role)->toBe('admin')->and(Hash::check('password123', $u->fresh()->password))->toBeTrue();
    $this->delete("/admin/users/{$u->id}")->assertRedirect();
    expect(User::count())->toBe(1);
});

it('protects the last admin and self-deletion', function () {
    $this->delete("/admin/users/{$this->admin->id}")->assertSessionHas('err');
    $this->put("/admin/users/{$this->admin->id}", ['name' => 'A', 'username' => $this->admin->username, 'email' => $this->admin->email, 'password' => '', 'role' => 'editor'])->assertSessionHasErrors('role');
    expect($this->admin->fresh()->role)->toBe('admin');
});

it('validates uniqueness', function () {
    $this->post('/admin/users', ['name' => 'Dup', 'username' => $this->admin->username, 'email' => 'new@x.io', 'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'editor'])->assertSessionHasErrors('username');
});
