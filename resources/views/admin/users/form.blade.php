<x-layouts.admin :title="$user->exists ? 'Edit user' : 'New user'">
<form method="post" action="{{ $user->exists ? admin_route('users.update', $user) : admin_route('users.store') }}" class="card max-w-xl p-6">
  @csrf @if($user->exists)@method('put')@endif
  <div class="field"><label for="name">Name</label><input id="name" name="name" class="input" value="{{ old('name', $user->name) }}" required maxlength="100"></div>
  <div class="field"><label for="username">Username</label><input id="username" name="username" class="input" value="{{ old('username', $user->username) }}" required maxlength="40"></div>
  <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required maxlength="255"></div>
  <div class="field">
    <label for="password">Password</label>
    <input id="password" type="password" name="password" class="input" autocomplete="new-password">
    @if($user->exists)<p class="hint">Leave blank to keep the current password.</p>@endif
  </div>
  <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" autocomplete="new-password"></div>
  <div class="field">
    <label for="role">Role</label>
    <select id="role" name="role" class="input">
      <option value="editor" @selected(old('role', $user->role ?? 'editor') === 'editor')>Editor</option>
      <option value="admin" @selected(old('role', $user->role ?? 'editor') === 'admin')>Admin</option>
    </select>
  </div>
  <div class="flex gap-2"><button class="btn btn-primary">Save</button><a href="{{ admin_route('users.index') }}" class="btn">Cancel</a></div>
</form>
</x-layouts.admin>
