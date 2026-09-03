<x-layouts.front>
  <x-slot:head><x-seo title="Search" :noindex="true"/></x-slot:head>
  <x-slot:sidebar><x-sidebar-tree :tree="$tree"/></x-slot:sidebar>
  <h1 class="text-3xl font-semibold tracking-tight">Search</h1>
  <form action="{{ url('/search') }}" method="get" class="mt-4" role="search">
    <label for="q" class="sr-only">Search</label>
    <input id="q" name="q" type="search" class="input w-full max-w-md" placeholder="Search docs…" value="{{ $q }}">
  </form>

  @if($results->count() > 0)
    <p class="mt-6 text-sm" style="color:var(--ink-3)">{{ $results->count() }} {{ \Illuminate\Support\Str::plural('result', $results->count()) }} for &ldquo;{{ $q }}&rdquo;</p>
    <ul class="mt-4 divide-y" style="border-color:var(--rule)">
      @foreach($results as $a)
        <li class="py-4">
          <a href="{{ $a->url() }}" class="text-lg font-medium">{{ $a->title }}</a>
          <p class="mt-1 text-xs" style="color:var(--ink-3)">{{ $a->category->ancestors()->push($a->category)->pluck('name')->implode(' › ') }}</p>
          <p class="mt-1 text-sm" style="color:var(--ink-2)">{{ $search->snippet($a, $q) }}</p>
        </li>
      @endforeach
    </ul>
  @else
    <p class="mt-6 text-sm" style="color:var(--ink-3)">No results for &ldquo;{{ $q }}&rdquo;. Try different words.</p>
  @endif
</x-layouts.front>
