<?php
namespace App\Support;

use Illuminate\Support\Str;

class HtmlSanitizer
{
    private const IFRAME_HOSTS = ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com', 'vimeo.com'];
    private const URL_ATTRS = ['href', 'src', 'xlink:href', 'action', 'formaction', 'poster', 'data', 'srcset'];

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
            if (str_starts_with($name, 'on')) { $attr->ownerElement?->removeAttribute($attr->nodeName); continue; }
            if (! in_array($name, self::URL_ATTRS, true)) { continue; }
            $allowed = $name === 'srcset' ? self::srcsetAllowed($value) : self::urlAllowed($attr->ownerElement, $name, $value);
            if (! $allowed) { $attr->ownerElement?->removeAttribute($attr->nodeName); }
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

    /**
     * Scheme allowlist for URL-bearing attributes. A denylist (e.g. matching "javascript:")
     * is bypassable via control characters or HTML entities inside the scheme (browsers ignore
     * both), so instead: decode entities, strip control chars, then only accept normal relative/
     * fragment/query paths, http(s)/mailto/tel, schemeless paths, or (for <img src> only) small
     * inline data:image URIs.
     */
    private static function urlAllowed(?\DOMElement $el, string $attrName, string $value): bool
    {
        $allowDataImage = $attrName === 'src' && $el instanceof \DOMElement && strtolower($el->nodeName) === 'img';

        return self::urlValueAllowed($value, $allowDataImage);
    }

    /**
     * `srcset` is a comma-separated list of "URL [descriptor]" candidates, not a single URL —
     * checking the raw attribute value against the allowlist lets a malicious candidate hide
     * behind a leading benign one (e.g. "a/b.jpg 1x, javascript:alert(1) 2x" starts with "/"
     * before any ":", so the whole-value check would pass it). Every candidate's URL portion
     * must individually pass the allowlist, or the whole attribute is dropped.
     *
     * srcset candidates are comma-separated and a data: URI contains its own comma, so data:
     * candidates are not supported here and cause the whole attribute to be dropped; use src for
     * data: images. We deliberately don't write a srcset parser to work around this — TinyMCE
     * never emits srcset, so failing safe (drop the attribute) is the correct behaviour.
     */
    private static function srcsetAllowed(string $value): bool
    {
        foreach (explode(',', $value) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') { continue; }
            $url = preg_split('/\s+/', $candidate, 2)[0] ?? '';
            if (! self::urlValueAllowed($url, false)) { return false; }
        }

        return true;
    }

    private static function urlValueAllowed(string $value, bool $allowDataImage): bool
    {
        $normalized = strtolower(preg_replace('/[\x00-\x20\x7f]+/', '', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($normalized === '') { return true; }
        // Also matches protocol-relative URLs ("//host/path"), which are intentionally allowed —
        // they carry the same risk class as an absolute https: link.
        if (preg_match('~^(/|#|\?|\./|\.\./)~', $normalized)) { return true; }
        if (preg_match('~^(https?:|mailto:|tel:)~', $normalized)) { return true; }

        if ($allowDataImage && preg_match('~^data:image/(png|jpeg|gif|webp);base64,~', $normalized)) {
            return true;
        }

        // No scheme at all: no ":" appears before the first "/", "?" or "#".
        $colonPos = strpos($normalized, ':');
        $stopPos = false;
        foreach (['/', '?', '#'] as $delim) {
            $p = strpos($normalized, $delim);
            if ($p !== false && ($stopPos === false || $p < $stopPos)) { $stopPos = $p; }
        }
        if ($colonPos === false || ($stopPos !== false && $colonPos > $stopPos)) { return true; }

        return false;
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
