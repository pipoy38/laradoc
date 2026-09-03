<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request) { return view('admin.profile.edit', ['user' => $request->user()]); }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'current_password' => 'required_with:password|nullable|current_password', 'password' => 'nullable|string|min:8|confirmed']);
        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        if (! empty($data['password'])) { $user->password = $data['password']; }
        $user->save();
        return back()->with('ok', 'Profile saved.');
    }
}
