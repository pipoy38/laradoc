<?php
namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Support\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function page(Request $request, SearchService $search)
    {
        $q = (string) $request->query('q', '');
        return view('front.search', ['q' => $q, 'results' => $search->search($q, 50), 'search' => $search]);
    }

    public function api(Request $request, SearchService $search)
    {
        return response()->json($search->search((string) $request->query('q', ''), 8)->map(fn ($a) => ['title' => $a->title, 'url' => $a->url(), 'path' => $a->category->ancestors()->push($a->category)->pluck('name')->implode(' › '), 'excerpt' => $a->excerpt])->values());
    }
}
