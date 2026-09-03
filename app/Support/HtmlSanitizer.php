<?php
namespace App\Support;

use Illuminate\Support\Str;

class HtmlSanitizer
{
    private const IFRAME_HOSTS = ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com', 'vimeo.com'];

    public static function clean(string $html): string
    {
        if (trim($html) === '') { return ''; }
        $doc = self::load($html);
        $xpath = new \DOMXPath($doc);
        foreach (['script', 'object', 'embed', 'style', 'form', 'input', 'button', 'meta', 'link', 'base'] as $tag) {
            foreach (iterator_to_array($doc->getElementsByTagName($tag)) as $el) { $el->parentNode?->removeChild($el); }
        }
        foreach (iterator_to_array($doc->getElementsByTagName('iframe')) as $el) {
            $host = strtolower((string) parse_url($el->getAttribute('src'), PHP_URL_HOST));
            if (! in_array($host, self::IFRAME_HOSTS, true)) { $el->parentNode?->removeChild($el); continue; }
            foreach (['srcdoc', 'onload'] as $a) { $el->removeAttribute($a); }
        }
        foreach ($xpath->query('//@*') as $attr) {
            $name = strtolower($attr->nodeName); $value = trim($attr->nodeValue ?? '');
            $isUrlAttr = in_array($name, ['href', 'src', 'xlink:href', 'action', 'formaction', 'poster', 'data'], true);
            if (str_starts_with($name, 'on') || ($isUrlAttr && preg_match('/^\s*(javascript|vbscript|data:text\/html)/i', $value))) {
                $attr->ownerElement?->removeAttribute($attr->nodeName);
            }
        }
        return self::innerHtml($doc, $xpath);
    }

    public static function toText(string $html): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('/<(\/?)(p|div|br|h[1-6]|li|tr|blockquote|pre)[^>]*>/i', ' ', $html) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    public static function excerpt(string $html, int $len = 200): string
    {
        // Str::limit measures $len in display-width units but appends '…' as its raw
        // (3-byte UTF-8) bytes, so byte-length can overrun $len by more than one byte.
        // Shave the budget by the marker's extra byte cost so strlen(excerpt) stays <= $len+1.
        $end = '…';
        $budget = max(0, $len - (strlen($end) - 1));
        return Str::limit(self::toText($html), $budget, $end);
    }

    private static function load(string $html): \DOMDocument
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        return $doc;
    }

    /**
     * PHP 8.5 deprecates DOMDocument::getElementById() outside a DTD-declared ID attribute
     * (our fragment has no DTD, so the id="__root" attribute is never registered as an ID type).
     * Locate the root via XPath instead, which raises no such notice.
     */
    private static function innerHtml(\DOMDocument $doc, \DOMXPath $xpath): string
    {
        $root = $xpath->query('//*[@id="__root"]')->item(0);
        $out = '';
        foreach ($root->childNodes as $child) { $out .= $doc->saveHTML($child); }
        return trim($out);
    }
}
