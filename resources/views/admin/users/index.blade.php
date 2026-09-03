<x-layouts.admin title="Users">
<div class="mb-4 flex items-center justify-between"><p class="text-sm" style="color:var(--ink-3)">Manage who can sign in to the admin.</p><a href="{{ admin_route('users.create') }}" class="btn btn-primary">New user</a></div>
<div class="card overflow-x-auto"><table class="table">
<thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th></th></tr></thead>
<tbody>
@forelse($users as $u)
<tr>
<td class="font-medium">{{ $u->name }}</td>
<td style="color:var(--ink-3)">{{ $u->username }}</td>
<td style="color:var(--ink-3)">{{ $u->email }}</td>
<td><span class="badge">{{ $u->role }}</span></td>
<td class="whitespace-nowrap text-right">
<a href="{{ admin_route('users.edit', $u) }}" class="btn btn-ghost">Edit</a>
@if($u->id !== auth()->id())
<form method="post" action="{{ admin_route('users.destroy', $u) }}" class="inline" onsubmit="return confirm('Delete this user?')">@csrf @method('delete')<button class="btn btn-ghost" style="color:var(--bad)">Delete</button></form>
@endif
</td>
</tr>
@empty<tr><td colspan="5" class="p-6 text-center" style="color:var(--ink-3)">No users yet.</td></tr>@endforelse
</tbody></table></div>
</x-layouts.admin>
