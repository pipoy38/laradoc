<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Tag;

class TagController extends Controller
{
    public function show(string $slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        $articles = $tag->articles()->published()->with('category')->orderBy('title')->get();

        return view('front.tag', ['tag' => $tag, 'articles' => $articles]);
    }
}
