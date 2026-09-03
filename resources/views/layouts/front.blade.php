<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
@isset($head){{ $head }}@else<title>{{ $title ?? setting('site_name', 'Laradoc') }}</title>@endisset
@if(setting('favicon_media_id') && ($fav = \App\Models\Media::find(setting('favicon_media_id'))))
<link rel="icon" href="{{ $fav->url() }}">
@endif
<x-theme-script scope="front"/>
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>[x-cloak]{display:none!important}</style>
</head>
<body class="flex min-h-dvh flex-col">
<header class="sticky top-0 z-30 border-b" style="background:var(--sheet);border-color:var(--rule-2)">
  <div class="mx-auto flex max-w-[1400px] items-center gap-3 px-4 py-2">
    <button type="button" class="btn btn-ghost md:hidden" aria-label="Open navigation" @click="$dispatch('open-sidebar')" x-data>☰</button>
    <a href="{{ url('/') }}" class="flex items-center gap-2 font-semibold no-underline" style="color:var(--ink)">
      @if(setting('logo_media_id') && ($logo = \App\Models\Media::find(setting('logo_media_id'))))
        <img src="{{ $logo->url() }}" alt="" class="h-8 w-auto">
      @endif
      <span>{{ setting('site_name', 'Laradoc') }}</span>
    </a>
    <nav class="hidden items-center gap-1 md:flex" aria-label="Site menu">
      @foreach(\App\Models\MenuItem::orderBy('sort_order')->get() as $item)
        <a href="{{ $item->url }}" class="btn btn-ghost" @if($item->new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
      @endforeach
    </nav>
    <div class="ml-auto flex items-center gap-2">
      <form action="{{ url('/search') }}" method="get" class="relative" x-data="siteSearch" @click.outside="open=false" role="search">
        <label for="q" class="sr-only">Search</label>
        <input id="q" name="q" type="search" class="input w-44 md:w-72" placeholder="Search docs…" autocomplete="off"
               x-model="q" @input="input" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter="if(!go()) $event.preventDefault()" @focus="open = results.length > 0">
        <ul x-show="open" x-cloak class="card absolute right-0 mt-1 w-80 overflow-hidden py-1 text-sm" role="listbox">
          <template x-for="(r, i) in results" :key="r.url">
            <li><a :href="r.url" class="block px-3 py-2 no-underline" :class="{'bg-[color-mix(in_srgb,var(--brand)_16%,transparent)]': i === active}" style="color:var(--ink)">
              <span class="block font-medium" x-text="r.title"></span><span class="block text-xs" style="color:var(--ink-3)" x-text="r.path"></span></a></li>
          </template>
        </ul>
      </form>
      <x-theme-toggle scope="front"/>
    </div>
  </div>
</header>
<div class="mx-auto flex w-full max-w-[1400px] flex-1 gap-8 px-4 py-6">
  @isset($sidebar)
  <div x-data="{ open: false }" @open-sidebar.window="open = true" @keydown.escape.window="open = false">
    <div x-show="open" x-cloak class="fixed inset-0 z-40 bg-black/40 md:hidden" @click="open = false"></div>
    <aside class="fixed inset-y-0 left-0 z-50 w-72 overflow-y-auto p-4 transition-transform md:static md:z-auto md:w-[280px] md:translate-x-0 md:p-0"
           :class="open ? 'translate-x-0' : '-translate-x-full'" style="background:var(--paper)" aria-label="Documentation">
      {{ $sidebar }}
    </aside>
  </div>
  @endisset
  <main class="min-w-0 max-w-[760px] flex-1">{{ $slot }}</main>
  @isset($aside)<aside class="hidden w-[220px] shrink-0 lg:block" aria-label="On this page">{{ $aside }}</aside>@endisset
</div>
<footer class="border-t px-4 py-6 text-center text-sm" style="border-color:var(--rule-2);color:var(--ink-3)">
  {!! setting('footer_text') ?: '© ' . date('Y') . ' ' . e(setting('site_name', 'Laradoc')) !!}
</footer>
</body>
</html>
