<?php
namespace App\Support;

use App\Models\Article;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function search(string $q, int $limit = 20): Collection
    {
        $q = trim(preg_replace('/\s+/', ' ', $q) ?? '');
        if (mb_strlen($q) < 2) { return collect(); }
        $query = Article::published()->with('category');
        if (DB::connection()->getDriverName() === 'mysql') {
            $boolean = collect(explode(' ', $q))->map(fn ($w) => '+'.preg_replace('/[+\-<>()~*"@]/', '', $w).'*')->implode(' ');
            $query->whereRaw('MATCH(title, excerpt, body_text) AGAINST(? IN BOOLEAN MODE)', [$boolean])
                  ->orderByRaw('MATCH(title, excerpt, body_text) AGAINST(? IN BOOLEAN MODE) DESC', [$boolean]);
        } else {
            foreach (explode(' ', $q) as $word) {
                $query->where(fn ($x) => $x->where('title', 'like', "%$word%")->orWhere('excerpt', 'like', "%$word%")->orWhere('body_text', 'like', "%$word%"));
            }
            $query->orderByRaw("CASE WHEN title LIKE ? THEN 0 ELSE 1 END, title", ["%$q%"]);
        }
        return $query->limit($limit)->get();
    }

    public function snippet(Article $a, string $q, int $len = 160): string
    {
        $text = $a->body_text; $pos = mb_stripos($text, explode(' ', trim($q))[0] ?? '');
        $start = $pos === false ? 0 : max(0, $pos - 60);
        return ($start > 0 ? '…' : '').mb_substr($text, $start, $len).(mb_strlen($text) > $start + $len ? '…' : '');
    }
}
