<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\PathRedirect;
use App\Support\AdminPrefix;
use App\Support\PathResolver;
use App\Support\Toc;

class PageController extends Controller
{
    public function __invoke(string $path, PathResolver $resolver)
    {
        $path = trim($path, '/');

        // This fallback route only runs when no other route (including admin.*) matched, so
        // reaching here with an /{prefix}/... path means it's an unknown admin URL. Bail out
        // with a plain 404 rather than letting the redirect lookup below possibly resolve a
        // typo'd admin path to a published doc page.
        if (explode('/', $path, 2)[0] === AdminPrefix::current()) {
            abort(404);
        }

        $hit = $resolver->resolve($path);
        if (! $hit) {
            if ($redirect = PathRedirect::where('from_path', $path)->first()) {
                $redirect->increment('hits');

                return redirect(url('/'.$redirect->to_path), 301);
            }
            abort(404);
        }
        if ($hit['type'] === 'category') {
            $category = $hit['category']->load(['children' => fn ($q) => $q->withCount(['articles' => fn ($q) => $q->published()])]);

            return view('front.category', ['category' => $category, 'articles' => $category->articles()->published()->get(), 'crumbs' => $category->ancestors()]);
        }
        $article = $hit['article']->load(['category', 'tags', 'editor', 'creator', 'ogImage']);
        $siblings = Article::published()->where('category_id', $article->category_id)->orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'slug', 'category_id']);
        $i = $siblings->search(fn ($s) => $s->id === $article->id);
        $toc = Toc::build($article->body_html);

        return view('front.article', ['article' => $article, 'body' => $toc['html'], 'toc' => $toc['items'], 'prev' => $siblings[$i - 1] ?? null, 'next' => $siblings[$i + 1] ?? null,
            'crumbs' => $article->category->ancestors()->push($article->category)]);
    }
}
