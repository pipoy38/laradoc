<x-layouts.auth title="Log in">
<form method="post" action="{{ admin_route('login.attempt') }}">@csrf
  <div class="field"><label for="login">Email or username</label><input id="login" name="login" class="input" value="{{ old('login') }}" required autofocus autocomplete="username">@error('login')<p class="error">{{ $message }}</p>@enderror</div>
  <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" class="input" required autocomplete="current-password"></div>
  <label class="mb-4 flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Remember me</label>
  <button class="btn btn-primary w-full justify-center">Log in</button>
  <p class="mt-3 text-center text-sm"><a href="{{ admin_route('password.request') }}">Forgot password?</a></p>
</form>
</x-layouts.auth>
