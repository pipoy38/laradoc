<?php
namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Support\PathResolver;

class HomeController extends Controller
{
    public function __invoke(PathResolver $resolver)
    {
        return view('front.home', ['recent' => Article::published()->with('category')->latest('updated_at')->limit(10)->get(), 'roots' => Category::whereNull('parent_id')->ordered()->withCount('articles')->get()]);
    }
}
