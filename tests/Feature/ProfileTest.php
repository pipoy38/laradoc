<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('lets a user update their own profile and password', function () {
    $u = User::factory()->create(['password' => 'oldpass123']);
    $this->actingAs($u)->get('/admin/profile')->assertOk();
    $this->put('/admin/profile', ['name' => 'New Name', 'email' => 'new@x.io', 'current_password' => 'oldpass123', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])->assertRedirect();
    expect($u->fresh()->name)->toBe('New Name')->and(Hash::check('newpass123', $u->fresh()->password))->toBeTrue();
    $this->put('/admin/profile', ['name' => 'X', 'email' => 'new@x.io', 'current_password' => 'wrong', 'password' => 'p', 'password_confirmation' => 'p'])->assertSessionHasErrors('current_password');
});
