<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Category;
use App\Models\PathRedirect;
use Illuminate\Support\Collection;

class PathResolver
{
    public function resolve(string $path, bool $publishedOnly = true): ?array
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn ($s) => $s !== ''));
        if ($segments === []) {
            return null;
        }

        // Walk categories as far as the path allows.
        $parentId = null;
        $category = null;
        $consumed = 0;
        foreach ($segments as $slug) {
            $next = Category::where('parent_id', $parentId)->where('slug', $slug)->first();
            if (! $next) {
                break;
            }
            $category = $next;
            $parentId = $next->id;
            $consumed++;
        }
        $remaining = array_slice($segments, $consumed);

        if ($remaining === [] && $category) {
            return ['type' => 'category', 'category' => $category];
        }
        if (count($remaining) === 1 && $category) {
            $q = Article::where('category_id', $category->id)->where('slug', $remaining[0]);
            if ($publishedOnly) {
                $q->published();
            }
            if ($article = $q->first()) {
                return ['type' => 'article', 'article' => $article];
            }
        }

        return null;
    }

    /** Root categories with nested children and published articles, ordered. */
    public function tree(): Collection
    {
        $categories = Category::ordered()->get();
        $articles = Article::published()->orderBy('sort_order')->orderBy('title')->get(['id', 'category_id', 'title', 'slug'])->groupBy('category_id');
        $byParent = $categories->groupBy(fn ($c) => $c->parent_id ?? 0);
        $build = function ($parentId, $parent = null) use (&$build, $byParent, $articles) {
            return ($byParent[$parentId] ?? collect())->each(function ($c) use (&$build, $articles, $parent) {
                $c->setRelation('parent', $parent);
                $c->setRelation('children', $build($c->id, $c));
                $c->setRelation('articles', $articles[$c->id] ?? collect());
            })->values();
        };

        return $build(0);
    }

    /** Record 301s for every public path under $category (or the article itself) when its path changes. */
    public function recordRedirects(string $oldPath, string $newPath, ?Category $category = null): void
    {
        if ($oldPath === $newPath) {
            return;
        }
        $this->redirect($oldPath, $newPath);
        if ($category) {
            foreach (Category::whereIn('id', $category->descendantIds())->get() as $cat) {
                $catNew = $cat->path();
                $catOld = $oldPath.substr($catNew, strlen($newPath));
                if ($cat->id !== $category->id) {
                    $this->redirect($catOld, $catNew);
                }
                foreach ($cat->articles as $article) {
                    $this->redirect($catOld.'/'.$article->slug, $catNew.'/'.$article->slug);
                }
            }
        }
    }

    private function redirect(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }
        PathRedirect::where('from_path', $to)->delete(); // a path that is live again must not redirect away
        PathRedirect::updateOrCreate(['from_path' => $from], ['to_path' => $to]);
        PathRedirect::where('to_path', $from)->update(['to_path' => $to]); // chain-collapse older redirects
    }
}
