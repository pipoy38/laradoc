<?php
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\PageController;
use App\Http\Controllers\Front\SearchController;
use App\Http\Controllers\Front\SitemapController;
use App\Http\Controllers\Front\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/search', [SearchController::class, 'page'])->name('search');
Route::get('/api/search', [SearchController::class, 'api'])->middleware('throttle:60,1')->name('api.search');
Route::get('/tags/{slug}', [TagController::class, 'show'])->name('tags.show');
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
//
// This is registered as a fallback route (not a plain wildcard) because the admin route
// group is added to the router AFTER routes/web.php finishes loading (bootstrap/app.php
// attaches it via withRouting()'s `then:` callback, which always runs after the `web:`
// file). A plain `Route::get('/{path}', ...)` would therefore win the match against every
// admin URL — Laravel matches non-fallback routes strictly in registration order — and
// swallow requests like /admin/login before the admin.* routes ever get a chance. Marking
// it ->fallback() makes Laravel try every other registered route first regardless of
// registration order, and only fall through to this one when nothing else matches.
Route::get('/{path}', PageController::class)->where('path', '.*')->name('front.page')->fallback();
