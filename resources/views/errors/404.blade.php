<x-layouts.front>
  <x-slot:head><x-seo title="Not found" :noindex="true"/></x-slot:head>
  <x-slot:sidebar><x-sidebar-tree :tree="$tree"/></x-slot:sidebar>
  <section class="py-10 text-center">
    <h1 class="text-3xl font-semibold tracking-tight">Page not found</h1>
    <p class="mt-2" style="color:var(--ink-2)">It may have moved or been unpublished.</p>
    <form action="{{ url('/search') }}" method="get" class="mx-auto mt-6 flex max-w-sm gap-2" role="search">
      <label for="q" class="sr-only">Search</label>
      <input id="q" name="q" type="search" class="input flex-1" placeholder="Search docs…" autocomplete="off">
      <button type="submit" class="btn btn-primary">Search</button>
    </form>
    <p class="mt-6"><a href="{{ url('/') }}">Go to the home page</a></p>
  </section>
</x-layouts.front>
