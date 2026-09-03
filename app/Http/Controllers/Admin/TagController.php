<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index()
    {
        return view('admin.tags.index', ['tags' => Tag::withCount('articles')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.tags.form', ['tag' => new Tag]);
    }

    public function store(Request $request)
    {
        Tag::create($this->validated($request));

        return redirect()->route('admin.tags.index')->with('ok', 'Tag created.');
    }

    public function edit(Tag $tag)
    {
        return view('admin.tags.form', ['tag' => $tag]);
    }

    public function update(Request $request, Tag $tag)
    {
        $tag->update($this->validated($request, $tag->id));

        return redirect()->route('admin.tags.index')->with('ok', 'Tag updated.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect()->route('admin.tags.index')->with('ok', 'Tag deleted.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('name', ''))]);

        return $request->validate([
            'name' => 'required|string|max:60',
            'slug' => ['required', 'string', 'max:60', Rule::unique('tags', 'slug')->ignore($id)],
        ]);
    }
}
