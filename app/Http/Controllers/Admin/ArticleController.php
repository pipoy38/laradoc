<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleRequest;
use App\Models\Article;
use App\Models\Tag;
use App\Support\HtmlSanitizer;
use App\Support\PathResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $q = Article::with(['category', 'editor', 'creator'])->latest('updated_at');
        if ($s = $request->input('status')) {
            $q->where('status', $s);
        }
        if ($c = $request->integer('category')) {
            $q->where('category_id', $c);
        }
        if ($t = $request->input('tag')) {
            $q->whereHas('tags', fn ($x) => $x->where('slug', $t));
        }
        if ($text = trim((string) $request->input('q'))) {
            $q->where(fn ($x) => $x->where('title', 'like', "%{$text}%")->orWhere('slug', 'like', "%{$text}%"));
        }

        return view('admin.articles.index', ['articles' => $q->paginate(25)->withQueryString(), 'options' => CategoryController::flatOptions(), 'tags' => Tag::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.articles.form', ['article' => new Article(['status' => 'draft']), 'tagString' => '']);
    }

    public function store(ArticleRequest $request)
    {
        $article = DB::transaction(function () use ($request) {
            $article = new Article($this->attributes($request));
            $article->created_by = $request->user()->id;
            $article->sort_order = (Article::where('category_id', $article->category_id)->max('sort_order') ?? -1) + 1;
            $article->save();
            $this->syncTags($article, $request->input('tags'));
            $this->snapshot($article, $request);

            return $article;
        });

        return redirect()->route('admin.articles.edit', $article)->with('ok', 'Article created.');
    }

    public function edit(Article $article)
    {
        $article->load(['tags', 'revisions.user']);

        return view('admin.articles.form', ['article' => $article, 'tagString' => $article->tags->pluck('name')->implode(', ')]);
    }

    public function update(ArticleRequest $request, Article $article, PathResolver $resolver)
    {
        DB::transaction(function () use ($request, $article, $resolver) {
            $oldPath = $article->path();
            $article->fill($this->attributes($request, $article));
            $article->updated_by = $request->user()->id;
            if ($article->isDirty('category_id')) {
                $article->sort_order = (Article::where('category_id', $article->category_id)->max('sort_order') ?? -1) + 1;
            }
            $article->save();
            $article->unsetRelation('category');
            $resolver->recordRedirects($oldPath, $article->path());
            $this->syncTags($article, $request->input('tags'));
            $this->snapshot($article, $request);
        });

        return redirect()->route('admin.articles.edit', $article)->with('ok', 'Saved.');
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return redirect()->route('admin.articles.index')->with('ok', 'Article deleted.');
    }

    private function attributes(ArticleRequest $request, ?Article $existing = null): array
    {
        $data = $request->safe()->except(['tags', 'change_note']);
        $data['body_html'] = HtmlSanitizer::clean($data['body_html']);
        $data['body_text'] = HtmlSanitizer::toText($data['body_html']);
        $data['excerpt'] = trim((string) ($data['excerpt'] ?? '')) ?: HtmlSanitizer::excerpt($data['body_html']);
        if ($data['status'] === 'published' && ! $existing?->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }

    private function syncTags(Article $article, ?string $csv): void
    {
        $ids = collect(explode(',', (string) $csv))->map(fn ($t) => trim($t))->filter()->unique(fn ($t) => Str::slug($t))
            ->map(fn ($name) => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id)->all();
        $article->tags()->sync($ids);
    }

    private function snapshot(Article $article, Request $request, ?string $note = null): void
    {
        $article->revisions()->create(['user_id' => $request->user()->id, 'title' => $article->title, 'body_html' => $article->body_html, 'excerpt' => $article->excerpt,
            'seo_title' => $article->seo_title, 'meta_description' => $article->meta_description, 'change_note' => $note ?? $request->input('change_note'), 'created_at' => now()]);
    }
}
