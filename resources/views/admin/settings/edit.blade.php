<x-layouts.admin title="Settings">
<div x-data="{ tab: @js($tab) }">
  <div class="tabs" role="tablist">
    <button type="button" role="tab" :class="{active: tab==='general'}" @click="tab='general'">General</button>
    <button type="button" role="tab" :class="{active: tab==='branding'}" @click="tab='branding'">Branding</button>
    <button type="button" role="tab" :class="{active: tab==='email'}" @click="tab='email'">Email</button>
    <button type="button" role="tab" :class="{active: tab==='admin_url'}" @click="tab='admin_url'">Admin URL</button>
  </div>

  <div x-show="tab==='general'" x-cloak class="card mt-4 max-w-2xl p-4">
    <form method="post" action="{{ admin_route('settings.update') }}">
      @csrf @method('put')
      <input type="hidden" name="section" value="general">
      <div class="field"><label for="site_name">Site name</label><input id="site_name" name="site_name" class="input" value="{{ old('site_name', $s->get('site_name')) }}" maxlength="80" required></div>
      <div class="field"><label for="site_tagline">Tagline</label><input id="site_tagline" name="site_tagline" class="input" value="{{ old('site_tagline', $s->get('site_tagline')) }}" maxlength="160"></div>
      <div class="field"><label for="home_intro">Home intro</label><textarea id="home_intro" name="home_intro" class="input" rows="4">{{ old('home_intro', $s->get('home_intro')) }}</textarea><p class="hint">Simple HTML allowed</p></div>
      <div class="field"><label for="footer_text">Footer text</label><textarea id="footer_text" name="footer_text" class="input" rows="2">{{ old('footer_text', $s->get('footer_text')) }}</textarea></div>
      <button class="btn btn-primary">Save</button>
    </form>
  </div>

  <div x-show="tab==='branding'" x-cloak class="card mt-4 max-w-2xl p-4" x-data="{
      logoId: @js((string) (old('logo_media_id', $s->get('logo_media_id')) ?: '')),
      faviconId: @js((string) (old('favicon_media_id', $s->get('favicon_media_id')) ?: '')),
      images: @js($images->mapWithKeys(fn ($m) => [(string) $m->id => $m->url()])),
    }">
    <form method="post" action="{{ admin_route('settings.update') }}">
      @csrf @method('put')
      <input type="hidden" name="section" value="branding">
      <div class="field">
        <label for="logo_media_id">Logo</label>
        <select id="logo_media_id" name="logo_media_id" class="input" x-model="logoId">
          <option value="">None</option>
          @foreach($images as $m)<option value="{{ $m->id }}">{{ $m->original_name }}</option>@endforeach
        </select>
        <img x-show="logoId && images[logoId]" x-cloak :src="images[logoId]" alt="" class="mt-2 h-12 w-auto">
      </div>
      <div class="field">
        <label for="favicon_media_id">Favicon</label>
        <select id="favicon_media_id" name="favicon_media_id" class="input" x-model="faviconId">
          <option value="">None</option>
          @foreach($images as $m)<option value="{{ $m->id }}">{{ $m->original_name }}</option>@endforeach
        </select>
        <img x-show="faviconId && images[faviconId]" x-cloak :src="images[faviconId]" alt="" class="mt-2 h-8 w-auto">
        <p class="hint">Upload images in Media first.</p>
      </div>
      <button class="btn btn-primary">Save</button>
    </form>
  </div>

  <div x-show="tab==='email'" x-cloak class="card mt-4 max-w-2xl p-4" x-data="{ mailer: @js(old('mail_mailer', $s->get('mail_mailer', 'log'))) }">
    <form method="post" action="{{ admin_route('settings.update') }}">
      @csrf @method('put')
      <input type="hidden" name="section" value="email">
      <div class="field">
        <label for="mail_mailer">Mailer</label>
        <select id="mail_mailer" name="mail_mailer" class="input" x-model="mailer">
          <option value="smtp">SMTP</option>
          <option value="sendmail">Sendmail</option>
          <option value="log">Log</option>
        </select>
        <p class="hint">log writes to storage/logs — good for testing</p>
      </div>
      <div x-show="mailer==='smtp'" x-cloak>
        <div class="field"><label for="mail_host">Host</label><input id="mail_host" name="mail_host" class="input" value="{{ old('mail_host', $s->get('mail_host')) }}" maxlength="255"></div>
        <div class="field"><label for="mail_port">Port</label><input id="mail_port" name="mail_port" type="number" class="input" value="{{ old('mail_port', $s->get('mail_port', 587)) }}" min="1" max="65535"></div>
        <div class="field"><label for="mail_username">Username</label><input id="mail_username" name="mail_username" class="input" value="{{ old('mail_username', $s->get('mail_username')) }}" maxlength="255"></div>
        <div class="field"><label for="mail_password">Password</label><input id="mail_password" name="mail_password" type="password" class="input" placeholder="•••••• (unchanged)" maxlength="255"></div>
        <div class="field">
          <label for="mail_encryption">Encryption</label>
          <select id="mail_encryption" name="mail_encryption" class="input">
            @php $enc = old('mail_encryption', $s->get('mail_encryption', 'tls')); @endphp
            <option value="tls" @selected($enc === 'tls')>TLS</option>
            <option value="ssl" @selected($enc === 'ssl')>SSL</option>
            <option value="none" @selected($enc === 'none')>None</option>
          </select>
        </div>
      </div>
      <div class="field"><label for="mail_from_address">From address</label><input id="mail_from_address" name="mail_from_address" type="email" class="input" value="{{ old('mail_from_address', $s->get('mail_from_address')) }}" maxlength="255"></div>
      <div class="field"><label for="mail_from_name">From name</label><input id="mail_from_name" name="mail_from_name" class="input" value="{{ old('mail_from_name', $s->get('mail_from_name')) }}" maxlength="80"></div>
      <button class="btn btn-primary">Save</button>
    </form>
    <form method="post" action="{{ admin_route('settings.test_mail') }}" class="mt-4">
      @csrf
      <button class="btn">Send test email to {{ auth()->user()->email }}</button>
    </form>
  </div>

  <div x-show="tab==='admin_url'" x-cloak class="card mt-4 max-w-2xl p-4">
    <p class="mb-2 text-sm">Current admin address: <strong>{{ url('/'.\App\Support\AdminPrefix::current()) }}</strong></p>
    <form method="post" action="{{ admin_route('settings.update') }}">
      @csrf @method('put')
      <input type="hidden" name="section" value="admin_url">
      <div class="field"><label for="admin_prefix">Admin prefix</label><input id="admin_prefix" name="admin_prefix" class="input" value="{{ old('admin_prefix', \App\Support\AdminPrefix::current()) }}" maxlength="32" required></div>
      <p class="hint">After saving you will be redirected to the new address. If you lose it, run <code>php artisan wiki:admin-prefix</code> on the server.</p>
      <button class="btn btn-primary">Save</button>
    </form>
  </div>
</div>
</x-layouts.admin>
