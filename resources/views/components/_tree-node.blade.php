<details @if($node->parent_id === null || in_array($node->id, $openIds, true)) open @endif>
  <summary>{{ $node->icon }} {{ $node->name }}</summary>
  <div>
    @foreach($node->articles as $a)
      <a href="{{ url('/'.$node->path().'/'.$a->slug) }}" @class(['active' => $current === $a->id]) @if($current === $a->id) aria-current="page" @endif>{{ $a->title }}</a>
    @endforeach
    @foreach($node->children as $child)@include('components._tree-node', ['node' => $child, 'current' => $current, 'openIds' => $openIds])@endforeach
  </div>
</details>
