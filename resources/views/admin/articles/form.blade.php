<x-layouts.admin :title="$article->exists ? 'Edit article' : 'New article'">
<form method="post" action="{{ $article->exists ? admin_route('articles.update', $article) : admin_route('articles.store') }}"
      x-data="{ tab: 'content', title: @js(old('title', $article->title)), slug: @js(old('slug', $article->slug)), touched: {{ $article->exists ? 'true' : 'false' }}, seoTitle: @js(old('seo_title', $article->seo_title)), metaDesc: @js(old('meta_description', $article->meta_description)) }"
      x-init="window.initEditor('#body_html', @js(admin_route('media.upload')), @js(csrf_token()))">
  @csrf @if($article->exists)@method('put')@endif
  <div class="mb-4 flex flex-wrap items-center gap-2">
    <input name="title" class="input text-lg font-semibold" placeholder="Article title" x-model="title" @input="if(!touched) slug = title.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')" required aria-label="Title">
  </div>
  <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
    <div>
      <div class="tabs" role="tablist">
        <button type="button" role="tab" :class="{active: tab==='content'}" @click="tab='content'">Content</button>
        <button type="button" role="tab" :class="{active: tab==='seo'}" @click="tab='seo'">SEO</button>
        @if($article->exists)<button type="button" role="tab" :class="{active: tab==='history'}" @click="tab='history'">History ({{ $article->revisions->count() }})</button>@endif
      </div>
      <div x-show="tab==='content'">
        <textarea id="body_html" name="body_html">{{ old('body_html', $article->body_html) }}</textarea>
        <div class="field mt-4"><label for="excerpt">Excerpt (optional)</label><textarea id="excerpt" name="excerpt" class="input" rows="2" maxlength="500">{{ old('excerpt', $article->excerpt) }}</textarea><p class="hint">Shown in search results and category lists. Generated from the body when left blank.</p></div>
      </div>
      <div x-show="tab==='seo'" x-cloak class="card p-4">
        <div class="field"><label for="seo_title">SEO title <span class="text-xs" x-text="(seoTitle||'').length + '/60'"></span></label><input id="seo_title" name="seo_title" class="input" x-model="seoTitle" maxlength="120"><p class="hint">Leave blank to use the article title.</p></div>
        <div class="field"><label for="meta_description">Meta description <span class="text-xs" x-text="(metaDesc||'').length + '/160'"></span></label><textarea id="meta_description" name="meta_description" class="input" rows="3" x-model="metaDesc" maxlength="320"></textarea></div>
        <div class="field"><label for="canonical_url">Canonical URL</label><input id="canonical_url" name="canonical_url" class="input" value="{{ old('canonical_url', $article->canonical_url) }}" placeholder="https://…"><p class="hint">Only if this content lives elsewhere first.</p></div>
        <label class="mb-4 flex items-center gap-2 text-sm"><input type="checkbox" name="noindex" value="1" @checked(old('noindex', $article->noindex))> Ask search engines not to index this page</label>
        <div class="field"><label for="og_image_id">Social share image</label><select id="og_image_id" name="og_image_id" class="input"><option value="">Site logo</option>@foreach(\App\Models\Media::latest()->get() as $m)@if($m->isImage())<option value="{{ $m->id }}" @selected((int) old('og_image_id', $article->og_image_id) === $m->id)>{{ $m->original_name }}</option>@endif @endforeach</select></div>
        <div class="rounded-lg border p-3" style="border-color:var(--rule-2)"><p class="text-xs" style="color:var(--ink-3)">Search preview</p>
          <p class="text-lg" style="color:#1a0dab" x-text="(seoTitle || title || 'Article title') + ' · {{ setting('site_name', 'Laradoc') }}'"></p>
          <p class="text-xs" style="color:var(--good)">{{ url('/') }}/…/<span x-text="slug"></span></p>
          <p class="text-sm" style="color:var(--ink-2)" x-text="metaDesc || 'Meta description will appear here.'"></p></div>
      </div>
      @if($article->exists)<div x-show="tab==='history'" x-cloak>@include('admin.articles._revisions')</div>@endif
    </div>
    <aside class="space-y-4">
      <div class="card p-4">
        <div class="field"><label for="status">Status</label><select id="status" name="status" class="input">@foreach(['draft' => 'Draft (hidden)', 'published' => 'Published'] as $v => $l)<option value="{{ $v }}" @selected(old('status', $article->status) === $v)>{{ $l }}</option>@endforeach</select></div>
        <div class="field"><label for="category_id">Category</label><select id="category_id" name="category_id" class="input" required><x-category-options :selected="old('category_id', $article->category_id)" blank="— Choose —"/></select></div>
        <div class="field"><label for="slug">Slug</label><input id="slug" name="slug" class="input" x-model="slug" @input="touched = true" pattern="[a-z0-9-]+"></div>
        <div class="field"><label for="tags">Tags</label><input id="tags" name="tags" class="input" value="{{ old('tags', $tagString) }}" list="tag-list" placeholder="comma, separated"><datalist id="tag-list">@foreach(\App\Models\Tag::orderBy('name')->get() as $t)<option value="{{ $t->name }}">@endforeach</datalist></div>
        <div class="field"><label for="change_note">Change note</label><input id="change_note" name="change_note" class="input" maxlength="200" placeholder="What changed?"></div>
        <button class="btn btn-primary w-full justify-center">{{ $article->exists ? 'Save' : 'Create' }}</button>
        @if($article->exists)<a href="{{ $article->url() }}" target="_blank" rel="noopener" class="btn mt-2 w-full justify-center">View on site ↗</a>@endif
      </div>
    </aside>
  </div>
</form>
</x-layouts.admin>
