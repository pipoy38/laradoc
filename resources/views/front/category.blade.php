<x-layouts.front>
  <x-slot:head><x-seo :title="$category->name" :description="$category->description"/></x-slot:head>
  <x-slot:sidebar><x-sidebar-tree :tree="$tree" :currentCategoryIds="$crumbs->pluck('id')->push($category->id)->all()"/></x-slot:sidebar>
  <nav aria-label="Breadcrumb" class="mb-3 text-sm" style="color:var(--ink-3)"><ol class="flex flex-wrap items-center gap-1">
    <li><a href="{{ url('/') }}">Home</a></li>@foreach($crumbs as $c)<li aria-hidden="true">›</li><li><a href="{{ $c->url() }}">{{ $c->name }}</a></li>@endforeach<li aria-hidden="true">›</li><li>{{ $category->name }}</li>
  </ol></nav>
  <h1 class="text-3xl font-semibold tracking-tight">{{ $category->icon }} {{ $category->name }}</h1>
  @if($category->description)<p class="mt-2 text-lg" style="color:var(--ink-2)">{{ $category->description }}</p>@endif

  @if($category->children->isNotEmpty())
  <section class="mt-8 grid gap-4 sm:grid-cols-2">
    @foreach($category->children as $child)
      <a href="{{ $child->url() }}" class="card block p-5 no-underline" style="color:var(--ink)">
        <div class="text-lg font-semibold">{{ $child->icon }} {{ $child->name }}</div>
        <p class="mt-1 text-sm" style="color:var(--ink-3)">{{ $child->articles_count }} {{ \Illuminate\Support\Str::plural('article', $child->articles_count) }}</p>
      </a>
    @endforeach
  </section>
  @endif

  @if($articles->isNotEmpty())
  <section class="mt-8">
    <ul class="divide-y" style="border-color:var(--rule)">
      @foreach($articles as $a)
        <li class="py-3">
          <a href="{{ $a->url() }}" class="text-lg font-medium">{{ $a->title }}</a>
          @if($a->excerpt)<p class="mt-1 text-sm" style="color:var(--ink-2)">{{ $a->excerpt }}</p>@endif
        </li>
      @endforeach
    </ul>
  </section>
  @endif
</x-layouts.front>
