<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function request() { return view('admin.auth.forgot'); }

    public function email(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));
        return back()->with('ok', 'If that email exists, a reset link has been sent.');
    }

    public function reset(Request $request, string $token) { return view('admin.auth.reset', ['token' => $token, 'email' => $request->query('email')]); }

    public function update(Request $request)
    {
        $data = $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|min:8|confirmed']);
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('ok', 'Password updated. Please log in.')
            : back()->withErrors(['email' => __($status)]);
    }
}
