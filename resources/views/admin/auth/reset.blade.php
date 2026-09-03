<x-layouts.auth title="Choose a new password">
<form method="post" action="{{ admin_route('password.update') }}">@csrf
  <input type="hidden" name="token" value="{{ $token }}">
  <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ $email }}" readonly autocomplete="email">@error('email')<p class="error">{{ $message }}</p>@enderror</div>
  <div class="field"><label for="password">New password</label><input id="password" type="password" name="password" class="input" required autocomplete="new-password">@error('password')<p class="error">{{ $message }}</p>@enderror</div>
  <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password"></div>
  <button class="btn btn-primary w-full justify-center">Update password</button>
</form>
</x-layouts.auth>
