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
        // front.home never renders an article count on these cards, so no withCount() here
        // (avoids the same draft-count leak this class of query had in PageController).
        return view('front.home', ['recent' => Article::published()->with('category')->latest('updated_at')->limit(10)->get(), 'roots' => Category::whereNull('parent_id')->ordered()->get()]);
    }
}
