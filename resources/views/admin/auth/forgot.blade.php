<x-layouts.auth title="Reset password">
<form method="post" action="{{ admin_route('password.email') }}">@csrf
  <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email') }}" required autofocus autocomplete="email">@error('email')<p class="error">{{ $message }}</p>@enderror</div>
  <button class="btn btn-primary w-full justify-center">Send reset link</button>
  <p class="mt-3 text-center text-sm"><a href="{{ admin_route('login') }}">Back to log in</a></p>
</form>
</x-layouts.auth>
