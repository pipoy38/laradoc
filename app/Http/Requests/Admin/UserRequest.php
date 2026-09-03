<?php
namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $user = $this->route('user'); $id = $user?->id;
        return [
            'name' => 'required|string|max:100',
            'username' => ['required', 'string', 'max:40', 'regex:/^[a-zA-Z0-9_.-]+$/', Rule::unique('users')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'editor']), function ($attr, $value, $fail) use ($user) {
                if ($user && $user->role === 'admin' && $value !== 'admin' && User::where('role', 'admin')->count() <= 1) { $fail('There must be at least one admin.'); }
            }],
        ];
    }
}
