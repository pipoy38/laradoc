<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User]);
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function store(UserRequest $request)
    {
        $data = $request->validated();
        User::create($data);

        return redirect()->route('admin.users.index')->with('ok', 'User created.');
    }

    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();
        $user->fill(['name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'], 'role' => $data['role']]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return redirect()->route('admin.users.index')->with('ok', 'User saved.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('err', 'You cannot delete your own account.');
        }
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('err', 'There must be at least one admin.');
        }
        $user->delete();

        return redirect()->route('admin.users.index')->with('ok', 'User deleted.');
    }
}
