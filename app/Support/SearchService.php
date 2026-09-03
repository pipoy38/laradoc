<?php

namespace App\Support;

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function search(string $q, int $limit = 20): Collection
    {
        $q = trim(preg_replace('/\s+/', ' ', $q) ?? '');
        if (mb_strlen($q) < 2) {
            return collect();
        }

        // Strip fulltext operator characters up front so both the MySQL boolean-mode
        // path and the LIKE fallback tokenize the query the same way, and drop any
        // word that becomes empty (e.g. a lone "+") so it can't produce a bare "+*"
        // boolean-mode term (MySQL syntax error) or a no-op LIKE clause.
        $words = collect(explode(' ', $q))
            ->map(fn ($w) => preg_replace('/[+\-<>()~*"@]/', '', $w))
            ->filter(fn ($w) => $w !== '')
            ->values();
        if ($words->isEmpty()) {
            return collect();
        }

        $query = Article::published()->with('category');

        // InnoDB's default innodb_ft_min_token_size is 3, so MATCH...AGAINST silently
        // ignores any shorter word — it wouldn't just skip that word, it would make the
        // whole boolean-mode query fail to find rows containing it. Fall back to LIKE
        // (which has no such minimum) whenever any word is too short for fulltext.
        if (DB::connection()->getDriverName() === 'mysql' && $words->every(fn ($w) => mb_strlen($w) >= 3)) {
            $boolean = $words->map(fn ($w) => '+'.$w.'*')->implode(' ');
            $query->whereRaw('MATCH(title, excerpt, body_text) AGAINST(? IN BOOLEAN MODE)', [$boolean])
                ->orderByRaw('MATCH(title, excerpt, body_text) AGAINST(? IN BOOLEAN MODE) DESC', [$boolean]);
        } else {
            $this->applyLike($query, $words, $q);
        }

        return $query->limit($limit)->get();
    }

    /** Shared by the non-MySQL driver and the MySQL short-word fallback. */
    private function applyLike(Builder $query, Collection $words, string $q): void
    {
        // escapeLike() only turns "%"/"_" in the pattern into literal-escaped sequences;
        // that's inert unless the SQL itself declares "\" as the escape character, and
        // MySQL's default LIKE escape ("\") is not universal — SQLite has none by default,
        // so a plain `where('title', 'like', $pattern)` would treat the escaped "\%" as a
        // literal backslash followed by a wildcard "%" there. whereRaw(... ESCAPE ?) makes
        // the escape character explicit on every driver.
        foreach ($words as $word) {
            $like = '%'.$this->escapeLike($word).'%';
            $query->where(fn ($x) => $x->whereRaw('title LIKE ? ESCAPE ?', [$like, '\\'])
                ->orWhereRaw('excerpt LIKE ? ESCAPE ?', [$like, '\\'])
                ->orWhereRaw('body_text LIKE ? ESCAPE ?', [$like, '\\']));
        }
        $like = '%'.$this->escapeLike($q).'%';
        $query->orderByRaw('CASE WHEN title LIKE ? ESCAPE ? THEN 0 ELSE 1 END, title', [$like, '\\']);
    }

    /** Escape LIKE wildcards so user input can't turn "%" or "_" into a pattern. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public function snippet(Article $a, string $q, int $len = 160): string
    {
        $text = $a->body_text;
        $pos = mb_stripos($text, explode(' ', trim($q))[0] ?? '');
        $start = $pos === false ? 0 : max(0, $pos - 60);

        return ($start > 0 ? '…' : '').mb_substr($text, $start, $len).(mb_strlen($text) > $start + $len ? '…' : '');
    }
}
