<x-layouts.admin :title="$category->exists ? 'Edit category' : 'New category'">
<form method="post" action="{{ $category->exists ? admin_route('categories.update', $category) : admin_route('categories.store') }}" class="card max-w-xl p-6" x-data="{ name: @js(old('name', $category->name)), slug: @js(old('slug', $category->slug)), touched: {{ $category->exists ? 'true' : 'false' }} }">
  @csrf @if($category->exists)@method('put')@endif
  <div class="field"><label for="name">Name</label><input id="name" name="name" class="input" x-model="name" @input="if(!touched) slug = name.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')" required></div>
  <div class="field"><label for="slug">Slug</label><input id="slug" name="slug" class="input" x-model="slug" @input="touched = true" pattern="[a-z0-9-]+"><p class="hint">Used in the URL. Lowercase letters, numbers and dashes.</p></div>
  <div class="field"><label for="parent_id">Parent</label><select id="parent_id" name="parent_id" class="input"><x-category-options :selected="old('parent_id', $parentId)" :exclude="$category->exists ? $category->descendantIds() : []"/></select></div>
  <div class="field"><label for="icon">Icon (optional emoji)</label><input id="icon" name="icon" class="input" value="{{ old('icon', $category->icon) }}" maxlength="32"></div>
  <div class="field"><label for="description">Description</label><textarea id="description" name="description" class="input" rows="3">{{ old('description', $category->description) }}</textarea></div>
  <div class="flex gap-2"><button class="btn btn-primary">Save</button><a href="{{ admin_route('categories.index') }}" class="btn">Cancel</a></div>
</form>
</x-layouts.admin>
