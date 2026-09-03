@props(['title' => 'Admin'])
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }} · {{ setting('site_name', 'Laradoc') }} admin</title>
<meta name="robots" content="noindex">
<x-theme-script scope="admin"/>
@vite(['resources/css/admin.css', 'resources/js/admin.js'])
<style>[x-cloak]{display:none!important}</style>
</head>
<body class="flex min-h-dvh" x-data="{ nav: false }">
<aside class="fixed inset-y-0 left-0 z-40 w-60 border-r p-4 transition-transform md:static md:translate-x-0" :class="nav ? 'translate-x-0' : '-translate-x-full'" style="background:var(--sheet);border-color:var(--rule-2)">
  <a href="{{ admin_route('dashboard') }}" class="mb-6 block font-semibold no-underline" style="color:var(--ink)">{{ setting('site_name', 'Laradoc') }}</a>
  <nav class="admin-nav space-y-1" aria-label="Admin">
    @php $links = [['dashboard','Dashboard','admin.dashboard',null],['articles.index','Articles','admin.articles.*',null],['categories.index','Categories','admin.categories.*',null],['tags.index','Tags','admin.tags.*',null],['media.index','Media','admin.media.*',null],['menu.index','Menu','admin.menu.*','admin'],['users.index','Users','admin.users.*','admin'],['settings.edit','Settings','admin.settings.*','admin']]; @endphp
    @foreach($links as [$name,$label,$pattern,$role])
      @if(\Illuminate\Support\Facades\Route::has("admin.$name") && (!$role || auth()->user()->role === $role))
        <a href="{{ admin_route($name) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">{{ $label }}</a>
      @endif
    @endforeach
  </nav>
</aside>
<div class="flex min-w-0 flex-1 flex-col">
  <header class="flex items-center gap-3 border-b px-4 py-2" style="background:var(--sheet);border-color:var(--rule-2)">
    <button class="btn btn-ghost md:hidden" @click="nav = !nav" aria-label="Menu">☰</button>
    <h1 class="text-lg font-semibold">{{ $title }}</h1>
    <div class="ml-auto flex items-center gap-2">
      <a href="{{ url('/') }}" class="btn btn-ghost" target="_blank" rel="noopener">View site ↗</a>
      <x-theme-toggle scope="admin"/>
      @if(\Illuminate\Support\Facades\Route::has('admin.profile.edit'))<a href="{{ admin_route('profile.edit') }}" class="btn btn-ghost">{{ auth()->user()->name }}</a>@endif
      <form method="post" action="{{ admin_route('logout') }}">@csrf<button class="btn btn-ghost">Log out</button></form>
    </div>
  </header>
  <main class="flex-1 p-4 md:p-6">
    @if(session('ok'))<div class="flash flash-ok" role="status">{{ session('ok') }}</div>@endif
    @if(session('err'))<div class="flash flash-err" role="alert">{{ session('err') }}</div>@endif
    @if($errors->any())<div class="flash flash-err" role="alert"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    {{ $slot }}
  </main>
</div>
</body>
</html>
