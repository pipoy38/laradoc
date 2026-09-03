<?php
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;

it('shows the login page', fn () => $this->get('/admin/login')->assertOk()->assertSee('Log in'));

it('logs in with email or username', function () {
    $u = User::factory()->create(['email' => 'a@b.c', 'username' => 'alice', 'password' => 'secret123']);
    $this->post('/admin/login', ['login' => 'alice', 'password' => 'secret123'])->assertRedirect('/admin');
    $this->assertAuthenticatedAs($u);
    auth()->logout();
    $this->post('/admin/login', ['login' => 'a@b.c', 'password' => 'secret123'])->assertRedirect('/admin');
});

it('rejects bad credentials and throttles', function () {
    User::factory()->create(['username' => 'alice', 'password' => 'secret123']);
    foreach (range(1, 5) as $i) { $this->from('/admin/login')->post('/admin/login', ['login' => 'alice', 'password' => 'nope'])->assertRedirect('/admin/login'); }
    $this->post('/admin/login', ['login' => 'alice', 'password' => 'secret123'])->assertStatus(429);
});

it('redirects guests and logs out', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->actingAs(User::factory()->create())->post('/admin/logout')->assertRedirect('/admin/login');
    $this->assertGuest();
});

it('blocks editors from admin-only routes', function () {
    $this->actingAs(User::factory()->create(['role' => 'editor']))->get('/admin/users')->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get('/admin/users')->assertOk();
});

it('sends a password reset link', function () {
    Notification::fake();
    $u = User::factory()->create();
    $this->post('/admin/forgot-password', ['email' => $u->email])->assertSessionHas('ok');
    Notification::assertSentTo($u, ResetPassword::class);
});
