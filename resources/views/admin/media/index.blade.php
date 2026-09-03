<x-layouts.admin title="Media">
<form method="post" action="{{ admin_route('media.upload') }}" enctype="multipart/form-data" class="card mb-4 flex flex-wrap items-center gap-3 p-4">@csrf
  <label for="file" class="text-sm">Upload image</label><input id="file" type="file" name="file" accept="image/png,image/jpeg,image/gif,image/webp" required class="text-sm"><button class="btn btn-primary">Upload</button>
  <span class="hint ml-auto">jpg, png, gif, webp · max 5 MB</span>
</form>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
@forelse($media as $m)
<div class="card overflow-hidden" x-data="{ copied: false }">
  <img src="{{ $m->url() }}" alt="{{ $m->alt }}" class="aspect-video w-full object-contain" style="background:var(--rule)" loading="lazy">
  <div class="p-3 text-xs" style="color:var(--ink-3)">
    <div class="truncate font-medium" style="color:var(--ink)" title="{{ $m->original_name }}">{{ $m->original_name }}</div>
    <div>{{ $m->width }}×{{ $m->height }} · {{ number_format($m->size / 1024) }} KB · {{ $m->uploader?->name }}</div>
    <form method="post" action="{{ admin_route('media.update', $m) }}" class="mt-2 flex gap-1">@csrf @method('patch')<input name="alt" class="input" placeholder="Alt text" value="{{ $m->alt }}" aria-label="Alt text"><button class="btn">Save</button></form>
    <div class="mt-2 flex gap-1">
      <button type="button" class="btn" @click="navigator.clipboard.writeText(@js($m->url())); copied = true; setTimeout(() => copied = false, 1500)" x-text="copied ? 'Copied' : 'Copy URL'"></button>
      <form method="post" action="{{ admin_route('media.destroy', $m) }}" onsubmit="return confirm('Delete this file? Articles that embed it will show a broken image.')">@csrf @method('delete')<button class="btn" style="color:var(--bad)">Delete</button></form>
    </div>
  </div>
</div>
@empty<p class="text-sm" style="color:var(--ink-3)">No uploads yet. Images added in the article editor appear here too.</p>@endforelse
</div>
<div class="mt-4">{{ $media->links() }}</div>
</x-layouts.admin>
