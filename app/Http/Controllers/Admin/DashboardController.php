<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Category;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'counts' => ['articles' => Article::count(), 'published' => Article::published()->count(), 'drafts' => Article::where('status', 'draft')->count(), 'categories' => Category::count(), 'users' => User::count()],
            'revisions' => ArticleRevision::with(['article', 'user'])->latest('id')->limit(10)->get(),
            'drafts' => Article::where('status', 'draft')->latest('updated_at')->limit(5)->get(),
        ]);
    }
}
