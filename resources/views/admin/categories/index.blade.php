<x-layouts.admin title="Categories">
<div class="mb-4 flex items-center justify-between"><p class="text-sm" style="color:var(--ink-3)">Categories group articles. Nest them by choosing a parent.</p><a href="{{ admin_route('categories.create') }}" class="btn btn-primary">New category</a></div>
<div class="card p-2">
@if($tree->isEmpty())<p class="p-4 text-sm" style="color:var(--ink-3)">No categories yet.</p>@endif
@foreach($tree as $node)@include('admin.categories._row', ['node' => $node])@endforeach
</div>
</x-layouts.admin>
