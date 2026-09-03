<x-layouts.admin :title="'Revision #'.$revision->id">
<p class="mb-4 text-sm" style="color:var(--ink-3)">{{ $revision->created_at->format('j M Y H:i') }} · {{ $revision->user?->name ?? 'Deleted user' }} · {{ $revision->change_note }}</p>
<div class="mb-4 flex gap-2"><a href="{{ admin_route('articles.edit', $article) }}" class="btn">Back to editor</a>
<form method="post" action="{{ admin_route('articles.revisions.restore', [$article, $revision]) }}" onsubmit="return confirm('Restore this revision?')">@csrf<button class="btn btn-primary">Restore this version</button></form></div>
<article class="card p-6"><h1 class="mb-4 text-2xl font-semibold">{{ $revision->title }}</h1><div class="prose-doc">{!! $revision->body_html !!}</div></article>
</x-layouts.admin>
