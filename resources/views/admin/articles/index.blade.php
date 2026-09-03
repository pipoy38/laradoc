<x-layouts.admin title="Articles">
<form method="get" class="mb-4 flex flex-wrap items-end gap-2">
  <input name="q" class="input w-56" placeholder="Search title…" value="{{ request('q') }}" aria-label="Search">
  <select name="status" class="input w-40" aria-label="Status"><option value="">Any status</option>@foreach(['draft','published'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
  <select name="category" class="input w-56" aria-label="Category"><option value="">Any category</option>@foreach($options as $id => $o)<option value="{{ $id }}" @selected(request()->integer('category')===$id)>{{ str_repeat('— ', $o['depth']) }}{{ $o['name'] }}</option>@endforeach</select>
  <select name="tag" class="input w-40" aria-label="Tag"><option value="">Any tag</option>@foreach($tags as $t)<option value="{{ $t->slug }}" @selected(request('tag')===$t->slug)>{{ $t->name }}</option>@endforeach</select>
  <button class="btn">Filter</button>
  <a href="{{ admin_route('articles.create') }}" class="btn btn-primary ml-auto">New article</a>
</form>
<div class="card overflow-x-auto"><table class="table">
<thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th></th></tr></thead>
<tbody>
@forelse($articles as $a)
<tr><td><a href="{{ admin_route('articles.edit', $a) }}" class="font-medium">{{ $a->title }}</a><div class="text-xs" style="color:var(--ink-3)">/{{ $a->path() }}</div></td>
<td>{{ $a->category->name }}</td><td><span class="badge badge-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
<td class="whitespace-nowrap text-xs" style="color:var(--ink-3)">{{ $a->updated_at->format('j M Y H:i') }}<br>{{ ($a->editor ?? $a->creator)?->name }}</td>
<td class="whitespace-nowrap text-right"><a href="{{ $a->url() }}" target="_blank" rel="noopener" class="btn btn-ghost">View</a>
<form method="post" action="{{ admin_route('articles.destroy', $a) }}" class="inline" onsubmit="return confirm('Delete this article and its history?')">@csrf @method('delete')<button class="btn btn-ghost" style="color:var(--bad)">Delete</button></form></td></tr>
@empty<tr><td colspan="5" class="p-6 text-center" style="color:var(--ink-3)">No articles match.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-4">{{ $articles->links() }}</div>
</x-layouts.admin>
