<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleRevision;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function show(Article $article, ArticleRevision $revision)
    {
        abort_unless($revision->article_id === $article->id, 404);
        return view('admin.revisions.show', ['article' => $article, 'revision' => $revision->load('user')]);
    }

    public function restore(Request $request, Article $article, ArticleRevision $revision)
    {
        abort_unless($revision->article_id === $article->id, 404);
        $article->fill($revision->only(['title', 'body_html', 'excerpt', 'seo_title', 'meta_description']));
        $article->body_text = \App\Support\HtmlSanitizer::toText($article->body_html);
        $article->updated_by = $request->user()->id;
        $article->save();
        $article->revisions()->create(['user_id' => $request->user()->id, 'title' => $article->title, 'body_html' => $article->body_html, 'excerpt' => $article->excerpt,
            'seo_title' => $article->seo_title, 'meta_description' => $article->meta_description, 'change_note' => "Restored revision #{$revision->id}", 'created_at' => now()]);
        return redirect()->route('admin.articles.edit', $article)->with('ok', "Restored revision #{$revision->id}.");
    }
}
