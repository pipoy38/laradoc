<x-layouts.front>
  <x-slot:head><x-seo :description="setting('site_tagline')"/></x-slot:head>
  <x-slot:sidebar><x-sidebar-tree :tree="$tree"/></x-slot:sidebar>
  <section class="mb-10">
    <h1 class="text-3xl font-semibold tracking-tight">{{ setting('site_name', 'Laradoc') }}</h1>
    @if(setting('site_tagline'))<p class="mt-1 text-lg" style="color:var(--ink-2)">{{ setting('site_tagline') }}</p>@endif
    @if(setting('home_intro'))<div class="prose-doc mt-4">{!! setting('home_intro') !!}</div>@endif
  </section>
  <section class="grid gap-4 sm:grid-cols-2">
    @foreach($roots as $c)
      <a href="{{ $c->url() }}" class="card block p-5 no-underline" style="color:var(--ink)">
        <div class="text-lg font-semibold">{{ $c->icon }} {{ $c->name }}</div>
        @if($c->description)<p class="mt-1 text-sm" style="color:var(--ink-2)">{{ $c->description }}</p>@endif
      </a>
    @endforeach
  </section>
  @if($recent->isNotEmpty())
  <section class="mt-10"><h2 class="mb-3 text-xl font-semibold">Recently updated</h2>
    <ul class="divide-y" style="border-color:var(--rule)">
      @foreach($recent as $a)<li class="flex items-baseline justify-between gap-4 py-2"><a href="{{ $a->url() }}">{{ $a->title }}</a><span class="shrink-0 text-xs" style="color:var(--ink-3)">{{ $a->category->name }} · {{ $a->updated_at->format('j M Y') }}</span></li>@endforeach
    </ul></section>
  @endif
</x-layouts.front>
