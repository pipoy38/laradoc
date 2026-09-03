@props(['tree', 'current' => null, 'currentCategoryIds' => []])
<nav class="tree" aria-label="Documentation">
  @foreach($tree as $node)@include('components._tree-node', ['node' => $node, 'current' => $current, 'openIds' => $currentCategoryIds])@endforeach
  @if($tree->isEmpty())<p class="px-2 text-sm" style="color:var(--ink-3)">No articles published yet.</p>@endif
</nav>
