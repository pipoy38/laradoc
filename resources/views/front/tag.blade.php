<x-layouts.front>
  <x-slot:head><x-seo :title="'Tagged: '.$tag->name"/></x-slot:head>
  <x-slot:sidebar><x-sidebar-tree :tree="$tree"/></x-slot:sidebar>
  <h1 class="text-3xl font-semibold tracking-tight">Tagged &ldquo;{{ $tag->name }}&rdquo;</h1>

  @if($articles->isNotEmpty())
    <ul class="mt-6 divide-y" style="border-color:var(--rule)">
      @foreach($articles as $a)
        <li class="py-4">
          <a href="{{ $a->url() }}" class="text-lg font-medium">{{ $a->title }}</a>
          <p class="mt-1 text-xs" style="color:var(--ink-3)">{{ $a->category->ancestors()->push($a->category)->pluck('name')->implode(' › ') }}</p>
          @if($a->excerpt)<p class="mt-1 text-sm" style="color:var(--ink-2)">{{ $a->excerpt }}</p>@endif
        </li>
      @endforeach
    </ul>
  @else
    <p class="mt-6 text-sm" style="color:var(--ink-3)">No articles tagged &ldquo;{{ $tag->name }}&rdquo;.</p>
  @endif
</x-layouts.front>
