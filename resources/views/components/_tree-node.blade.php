{{-- One category open at a time: a root opens only when it holds the current page (or, on pages
     with no current category, the first root), and every sibling stays collapsed. Deeper levels
     open only along the active branch. Alpine turns the root <details> into an accordion. --}}
@php $isRoot = $node->parent_id === null; $open = in_array($node->id, $openIds, true) || ($isRoot && empty($openIds) && $loop->first); @endphp
<details @if($open) open @endif @if($isRoot) x-data @toggle="if ($el.open) $el.parentElement.querySelectorAll(':scope > details[open]').forEach(d => { if (d !== $el) d.open = false })" @endif>
  <summary>{{ $node->icon }} {{ $node->name }}</summary>
  <div>
    @foreach($node->articles as $a)
      <a href="{{ url('/'.$node->path().'/'.$a->slug) }}" @class(['active' => $current === $a->id]) @if($current === $a->id) aria-current="page" @endif>{{ $a->title }}</a>
    @endforeach
    @foreach($node->children as $child)@include('components._tree-node', ['node' => $child, 'current' => $current, 'openIds' => $openIds])@endforeach
  </div>
</details>
