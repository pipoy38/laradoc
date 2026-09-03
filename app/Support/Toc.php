<?php
namespace App\Support;

use Illuminate\Support\Str;

class Toc
{
    public static function build(string $html): array
    {
        $items = []; $used = [];
        $out = preg_replace_callback('/<h([23])([^>]*)>(.*?)<\/h\1>/is', function ($m) use (&$items, &$used) {
            $level = (int) $m[1]; $attrs = $m[2]; $inner = $m[3];
            $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (preg_match('/\sid="([^"]+)"/', $attrs, $idm)) { $id = $idm[1]; }
            else {
                $base = Str::slug($text) ?: 'section'; $id = $base; $n = 2;
                while (isset($used[$id])) { $id = $base.'-'.$n++; }
                $attrs = ' id="'.$id.'"'.$attrs;
            }
            $used[$id] = true;
            $items[] = ['id' => $id, 'text' => $text, 'level' => $level];
            return "<h{$level}{$attrs}>{$inner}</h{$level}>";
        }, $html);
        return ['html' => $out ?? $html, 'items' => $items];
    }
}
