<x-layouts.admin title="Profile">
<form method="post" action="{{ admin_route('profile.update') }}" class="card max-w-xl p-6">
  @csrf @method('put')
  <div class="field"><label for="name">Name</label><input id="name" name="name" class="input" value="{{ old('name', $user->name) }}" required maxlength="100"></div>
  <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required maxlength="255"></div>
  <hr class="my-4" style="border-color:var(--rule)">
  <h2 class="mb-2 text-sm font-semibold">Change password</h2>
  <div class="field"><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" class="input" autocomplete="current-password"></div>
  <div class="field"><label for="password">New password</label><input id="password" type="password" name="password" class="input" autocomplete="new-password"><p class="hint">Leave blank to keep your current password.</p></div>
  <div class="field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" autocomplete="new-password"></div>
  <button class="btn btn-primary">Save</button>
</form>
</x-layouts.admin>
