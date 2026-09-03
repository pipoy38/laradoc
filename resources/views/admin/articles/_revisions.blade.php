<div class="card overflow-x-auto"><table class="table"><thead><tr><th>#</th><th>When</th><th>Who</th><th>Note</th><th></th></tr></thead><tbody>
@foreach($article->revisions as $r)
<tr><td>{{ $r->id }}</td><td class="whitespace-nowrap">{{ $r->created_at->format('j M Y H:i') }}</td><td>{{ $r->user?->name ?? 'Deleted user' }}</td><td>{{ $r->change_note }}</td>
<td class="whitespace-nowrap text-right"><a href="{{ admin_route('articles.revisions.show', [$article, $r]) }}" class="btn btn-ghost">View</a>
@unless($loop->first)<form method="post" action="{{ admin_route('articles.revisions.restore', [$article, $r]) }}" class="inline" onsubmit="return confirm('Restore this revision? The current version is kept in history.')">@csrf<button class="btn btn-ghost">Restore</button></form>@endunless</td></tr>
@endforeach</tbody></table></div>
