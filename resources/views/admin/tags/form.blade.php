<x-layouts.admin :title="$tag->exists ? 'Edit tag' : 'New tag'">
<form method="post" action="{{ $tag->exists ? admin_route('tags.update', $tag) : admin_route('tags.store') }}" class="card max-w-sm p-4">
  @csrf @if($tag->exists)@method('put')@endif
  <div class="field"><label for="name">Name</label><input id="name" name="name" class="input" value="{{ old('name', $tag->name) }}" required maxlength="60"></div>
  <button class="btn btn-primary">{{ $tag->exists ? 'Save' : 'Create' }}</button>
</form>
</x-layouts.admin>
