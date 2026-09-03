<x-layouts.admin title="Dashboard">
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  @foreach(['articles' => 'Articles', 'published' => 'Published', 'drafts' => 'Drafts', 'categories' => 'Categories'] as $k => $label)
    <div class="card p-4"><div class="text-sm" style="color:var(--ink-3)">{{ $label }}</div><div class="text-3xl font-semibold tabular-nums">{{ $counts[$k] }}</div></div>
  @endforeach
</div>
<div class="mt-6 grid gap-6 lg:grid-cols-2">
  <section class="card p-4"><h2 class="mb-3 font-semibold">Recent edits</h2>
    @forelse($revisions as $r)
      <div class="flex items-baseline justify-between gap-3 border-b py-2 text-sm last:border-0" style="border-color:var(--rule)">
        <a href="{{ admin_route('articles.edit', $r->article) }}">{{ $r->title }}</a>
        <span class="shrink-0 text-xs" style="color:var(--ink-3)">{{ $r->user?->name ?? 'Deleted user' }} · {{ $r->created_at->diffForHumans() }}</span>
      </div>
    @empty<p class="text-sm" style="color:var(--ink-3)">No edits yet. <a href="{{ admin_route('articles.create') }}">Write the first article.</a></p>@endforelse
  </section>
  <section class="card p-4"><h2 class="mb-3 font-semibold">Drafts waiting</h2>
    @forelse($drafts as $d)<div class="border-b py-2 text-sm last:border-0" style="border-color:var(--rule)"><a href="{{ admin_route('articles.edit', $d) }}">{{ $d->title }}</a></div>
    @empty<p class="text-sm" style="color:var(--ink-3)">Nothing in draft.</p>@endforelse
  </section>
</div>
</x-layouts.admin>
