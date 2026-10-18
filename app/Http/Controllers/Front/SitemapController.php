<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Carbon\Carbon;

class SitemapController extends Controller
{
    public function sitemap()
    {
        $urls = [['loc' => url('/'), 'lastmod' => Article::published()->max('updated_at')]];
        foreach (Category::ordered()->get() as $c) {
            $urls[] = ['loc' => $c->url(), 'lastmod' => null];
        }
        foreach (Article::published()->where('noindex', false)->with('category')->get() as $a) {
            $urls[] = ['loc' => $a->url(), 'lastmod' => $a->updated_at];
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml .= '<url><loc>'.e($u['loc']).'</loc>'.($u['lastmod'] ? '<lastmod>'.Carbon::parse($u['lastmod'])->toAtomString().'</lastmod>' : '').'</url>';
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml']);
    }

    public function robots()
    {
        // The admin prefix is deliberately NOT listed: robots.txt is public, and a Disallow line
        // would reveal the hidden admin address. Admin pages send noindex themselves and nothing
        // on the public site links to them, so crawlers never find them anyway.
        return response("User-agent: *\nDisallow: /api/\nAllow: /\nSitemap: ".url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
