@props(['selected' => null, 'exclude' => [], 'blank' => '— None (top level) —'])
@php $opts = \App\Http\Controllers\Admin\CategoryController::flatOptions(); @endphp
@if($blank !== false)<option value="">{{ $blank }}</option>@endif
@foreach($opts as $id => $o)
  @continue(in_array($id, $exclude, true))
  <option value="{{ $id }}" @selected((int) $selected === $id)>{{ str_repeat('— ', $o['depth']) }}{{ $o['name'] }}</option>
@endforeach
