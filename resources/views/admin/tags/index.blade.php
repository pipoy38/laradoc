<x-layouts.admin title="Tags">
<form method="post" action="{{ admin_route('tags.store') }}" class="mb-4 flex flex-wrap items-end gap-2">
  @csrf
  <div class="field mb-0"><label for="name">New tag</label><input id="name" name="name" class="input w-56" placeholder="Tag name" required maxlength="60"></div>
  <button class="btn btn-primary">Add tag</button>
</form>
<div class="card overflow-x-auto"><table class="table">
<thead><tr><th>Name</th><th>Slug</th><th>Articles</th><th></th></tr></thead>
<tbody>
@forelse($tags as $t)
<tr><td class="font-medium">{{ $t->name }}</td><td style="color:var(--ink-3)">{{ $t->slug }}</td><td>{{ $t->articles_count }}</td>
<td class="whitespace-nowrap text-right">
<a href="{{ admin_route('tags.edit', $t) }}" class="btn btn-ghost">Edit</a>
<form method="post" action="{{ admin_route('tags.destroy', $t) }}" class="inline" onsubmit="return confirm('Delete this tag?')">@csrf @method('delete')<button class="btn btn-ghost" style="color:var(--bad)">Delete</button></form>
</td></tr>
@empty<tr><td colspan="4" class="p-6 text-center" style="color:var(--ink-3)">No tags yet.</td></tr>@endforelse
</tbody></table></div>
</x-layouts.admin>
