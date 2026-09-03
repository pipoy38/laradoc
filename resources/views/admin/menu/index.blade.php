<x-layouts.admin title="Menu">
<p class="mb-4 text-sm" style="color:var(--ink-3)">Links shown in the site header, left to right.</p>
<form method="post" action="{{ admin_route('menu.store') }}" class="card mb-4 flex flex-wrap items-end gap-2 p-4">
  @csrf
  <div class="field mb-0"><label for="label">Label</label><input id="label" name="label" class="input w-40" required maxlength="60"></div>
  <div class="field mb-0"><label for="url">URL</label><input id="url" name="url" class="input w-64" required maxlength="500" placeholder="https://… or /path"></div>
  <label class="flex items-center gap-1 pb-2 text-sm"><input type="checkbox" name="new_tab" value="1"> New tab</label>
  <button class="btn btn-primary">Add link</button>
</form>
<div class="card overflow-x-auto">
@if($items->isEmpty())<p class="p-4 text-sm" style="color:var(--ink-3)">No menu items yet.</p>@endif
@foreach($items as $item)
<div class="flex flex-wrap items-end gap-2 border-b px-4 py-3 last:border-0" style="border-color:var(--rule)">
  <form method="post" action="{{ admin_route('menu.update', $item) }}" class="flex flex-wrap items-end gap-2">
    @csrf @method('put')
    <div class="field mb-0"><label for="label-{{ $item->id }}">Label</label><input id="label-{{ $item->id }}" name="label" class="input w-40" value="{{ $item->label }}" required maxlength="60"></div>
    <div class="field mb-0"><label for="url-{{ $item->id }}">URL</label><input id="url-{{ $item->id }}" name="url" class="input w-64" value="{{ $item->url }}" required maxlength="500"></div>
    <label class="flex items-center gap-1 pb-2 text-sm"><input type="checkbox" name="new_tab" value="1" @checked($item->new_tab)> New tab</label>
    <button class="btn">Save</button>
  </form>
  <span class="ml-auto flex items-center gap-1">
    <form method="post" action="{{ admin_route('menu.move', $item) }}">@csrf<input type="hidden" name="dir" value="up"><button class="btn btn-ghost" aria-label="Move up">↑</button></form>
    <form method="post" action="{{ admin_route('menu.move', $item) }}">@csrf<input type="hidden" name="dir" value="down"><button class="btn btn-ghost" aria-label="Move down">↓</button></form>
    <form method="post" action="{{ admin_route('menu.destroy', $item) }}" onsubmit="return confirm('Delete this menu item?')">@csrf @method('delete')<button class="btn btn-ghost" style="color:var(--bad)">Delete</button></form>
  </span>
</div>
@endforeach
</div>
</x-layouts.admin>
