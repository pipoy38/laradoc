<div class="flex items-center gap-2 border-b px-2 py-2 text-sm last:border-0" style="border-color:var(--rule);padding-left:{{ 0.5 + $node->depth * 1.25 }}rem">
  <span class="font-medium">{{ $node->icon }} {{ $node->name }}</span>
  <span class="text-xs" style="color:var(--ink-3)">/{{ $node->path() }} · {{ $node->articles_count }} {{ Str::plural('article', $node->articles_count) }}</span>
  <span class="ml-auto flex items-center gap-1">
    <form method="post" action="{{ admin_route('categories.move', $node) }}">@csrf<input type="hidden" name="dir" value="up"><button class="btn btn-ghost" aria-label="Move up">↑</button></form>
    <form method="post" action="{{ admin_route('categories.move', $node) }}">@csrf<input type="hidden" name="dir" value="down"><button class="btn btn-ghost" aria-label="Move down">↓</button></form>
    <a href="{{ admin_route('categories.create', ['parent' => $node->id]) }}" class="btn btn-ghost">+ Sub</a>
    <a href="{{ admin_route('categories.edit', $node) }}" class="btn btn-ghost">Edit</a>
    <form method="post" action="{{ admin_route('categories.destroy', $node) }}" onsubmit="return confirm('Delete this category?')">@csrf @method('delete')<button class="btn btn-ghost" style="color:var(--bad)">Delete</button></form>
  </span>
</div>
@foreach($node->children as $child)@include('admin.categories._row', ['node' => $child])@endforeach
