<?php
use App\Support\HtmlSanitizer;

it('strips scripts, handlers and javascript urls but keeps content', function () {
    $in = '<p onclick="x()">Hi <a href="javascript:alert(1)">bad</a> <a href="/guides/x">ok</a></p><script>alert(1)</script><object data="x"></object><embed src="x">';
    $out = HtmlSanitizer::clean($in);
    expect($out)->not->toContain('<script')->not->toContain('onclick')->not->toContain('javascript:')->not->toContain('<object')->not->toContain('<embed')
        ->toContain('href="/guides/x"')->toContain('Hi');
});

it('allows iframes only from youtube and vimeo', function () {
    $out = HtmlSanitizer::clean('<iframe src="https://www.youtube.com/embed/abc"></iframe><iframe src="https://evil.example/x"></iframe><iframe src="https://player.vimeo.com/video/1"></iframe>');
    expect(substr_count($out, '<iframe'))->toBe(2)->and($out)->not->toContain('evil.example');
});

it('extracts text and excerpts', function () {
    expect(HtmlSanitizer::toText('<h2>Title</h2><p>Body &amp; more</p>'))->toBe('Title Body & more')
        ->and(strlen(HtmlSanitizer::excerpt('<p>'.str_repeat('word ', 100).'</p>', 50)))->toBeLessThanOrEqual(51);
});
