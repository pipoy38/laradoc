<x-layouts.front>
  <x-slot:head><x-seo :title="$article->seo_title ?: $article->title" :description="$article->meta_description ?: $article->excerpt" :canonical="$article->canonical_url" :noindex="$article->noindex" :image="$article->ogImage?->url()" type="article"
      :jsonld="['@context' => 'https://schema.org', '@type' => 'TechArticle', 'headline' => $article->title, 'description' => $article->meta_description ?: $article->excerpt, 'dateModified' => $article->updated_at->toIso8601String(), 'datePublished' => optional($article->published_at)->toIso8601String(), 'author' => ['@type' => 'Person', 'name' => ($article->editor ?? $article->creator)?->name ?? setting('site_name')], 'mainEntityOfPage' => $article->url()]"/></x-slot:head>
  <x-slot:sidebar><x-sidebar-tree :tree="$tree" :current="$article->id" :currentCategoryIds="$crumbs->pluck('id')->all()"/></x-slot:sidebar>
  <nav aria-label="Breadcrumb" class="mb-3 text-sm" style="color:var(--ink-3)"><ol class="flex flex-wrap items-center gap-1">
    <li><a href="{{ url('/') }}">Home</a></li>@foreach($crumbs as $c)<li aria-hidden="true">›</li><li><a href="{{ $c->url() }}">{{ $c->name }}</a></li>@endforeach
  </ol></nav>
  <article>
    <h1 class="text-3xl font-semibold tracking-tight">{{ $article->title }}</h1>
    <p class="mt-2 text-sm" style="color:var(--ink-3)">Updated {{ $article->updated_at->format('j M Y') }}@if($who = ($article->editor ?? $article->creator)) by {{ $who->name }}@endif</p>
    @if($article->tags->isNotEmpty())<p class="mt-2 flex flex-wrap gap-1">@foreach($article->tags as $t)<a href="{{ url('/tags/'.$t->slug) }}" class="badge no-underline">{{ $t->name }}</a>@endforeach</p>@endif
    @if($toc)<details class="card mb-4 p-3 lg:hidden"><summary class="cursor-pointer text-sm font-semibold">On this page</summary><ul>@foreach($toc as $h)<li><a href="#{{ $h['id'] }}">{{ $h['text'] }}</a></li>@endforeach</ul></details>@endif
    <div class="prose-doc mt-6">{!! $body !!}</div>
  </article>
  <nav class="mt-10 flex justify-between gap-4 border-t pt-4 text-sm" style="border-color:var(--rule-2)" aria-label="Previous and next">
    <div>@if($prev)<a href="{{ url('/'.$article->category->path().'/'.$prev->slug) }}">← {{ $prev->title }}</a>@endif</div>
    <div class="text-right">@if($next)<a href="{{ url('/'.$article->category->path().'/'.$next->slug) }}">{{ $next->title }} →</a>@endif</div>
  </nav>
  @if($toc)
  <x-slot:aside>
    <div class="sticky top-20 text-sm" x-data="toc">
      <p class="mb-2 font-semibold" style="color:var(--ink-3)">On this page</p>
      <ul class="space-y-1 border-l" style="border-color:var(--rule-2)">
        @foreach($toc as $h)<li><a href="#{{ $h['id'] }}" class="block border-l-2 -ml-px py-0.5 no-underline {{ $h['level'] === 3 ? 'pl-5' : 'pl-3' }}" :class="current === @js($h['id']) ? 'font-medium' : ''" :style="current === @js($h['id']) ? 'border-color:var(--brand-deep);color:var(--brand-deep)' : 'border-color:transparent;color:var(--ink-2)'">{{ $h['text'] }}</a></li>@endforeach
      </ul>
    </div>
  </x-slot:aside>
  @endif
</x-layouts.front>
