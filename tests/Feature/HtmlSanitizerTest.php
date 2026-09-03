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

it('blocks a tab-entity-obfuscated javascript url', function () {
    $out = HtmlSanitizer::clean('<a href="java&#09;script:alert(1)">x</a>');
    expect($out)->not->toContain('href');
});

it('blocks a javascript url with mixed case and leading whitespace', function () {
    $out = HtmlSanitizer::clean('<a href=" JaVaScRiPt:alert(1)">x</a>');
    expect($out)->not->toContain('href');
});

it('keeps safe relative, fragment, query and scheme-allowlisted urls verbatim', function () {
    expect(HtmlSanitizer::clean('<a href="guides/install">x</a>'))->toContain('href="guides/install"');
    expect(HtmlSanitizer::clean('<a href="../up">x</a>'))->toContain('href="../up"');
    expect(HtmlSanitizer::clean('<a href="#toc">x</a>'))->toContain('href="#toc"');
    expect(HtmlSanitizer::clean('<a href="mailto:a@b.c">x</a>'))->toContain('href="mailto:a@b.c"');
    expect(HtmlSanitizer::clean('<a href="https://example.com/x?y=1">x</a>'))->toContain('href="https://example.com/x?y=1"');
});

it('allows small inline data:image uris only on img src', function () {
    expect(HtmlSanitizer::clean('<img src="data:image/png;base64,iVBORw0KGgo=">'))->toContain('src="data:image/png;base64,iVBORw0KGgo="');
    $out = HtmlSanitizer::clean('<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>');
    expect($out)->not->toContain('href');
    $out = HtmlSanitizer::clean('<img src="data:text/html,x">');
    expect($out)->not->toContain('src');
});

it('strips javascript urls from xlink:href inside svg', function () {
    $out = HtmlSanitizer::clean('<svg><a xlink:href="javascript:alert(1)"><text>x</text></a></svg>');
    expect($out)->not->toContain('xlink:href');
});

it('drops the whole srcset when any candidate url is unsafe', function () {
    $out = HtmlSanitizer::clean('<img srcset="a/b.jpg 1x, javascript:alert(1) 2x">');
    expect($out)->not->toContain('srcset');
});

it('keeps a srcset verbatim when every candidate url is safe', function () {
    $out = HtmlSanitizer::clean('<img srcset="/img/a.jpg 1x, https://cdn.example/a@2x.jpg 2x">');
    expect($out)->toContain('srcset="/img/a.jpg 1x, https://cdn.example/a@2x.jpg 2x"');
});

it('keeps src but drops srcset when srcset contains a data: image (unsupported due to its own comma)', function () {
    $out = HtmlSanitizer::clean('<img src="data:image/png;base64,iVBORw0KGgo=" srcset="data:image/png;base64,iVBORw0KGgo= 1x">');
    expect($out)->toContain('src="data:image/png;base64,iVBORw0KGgo="')->not->toContain('srcset');
});

it('strips svg animation vectors that can rewrite attributes to javascript urls over time', function () {
    $out = HtmlSanitizer::clean('<svg><a><set attributeName="href" to="javascript:alert(3)"/><text>x</text></a></svg>');
    expect($out)->not->toContain('<set');

    $out = HtmlSanitizer::clean('<svg><a><animate attributeName="href" values="javascript:alert(1)"/></a></svg>');
    expect($out)->not->toContain('<animate');

    $out = HtmlSanitizer::clean('<svg><animateTransform attributeName="transform"/></svg>');
    expect($out)->not->toContain('<animatetransform');

    $out = HtmlSanitizer::clean('<svg><animateMotion/></svg>');
    expect($out)->not->toContain('<animatemotion');

    $out = HtmlSanitizer::clean('<svg><use href="evil.svg#x"/></svg>');
    expect($out)->not->toContain('<use');

    $out = HtmlSanitizer::clean('<svg><foreignObject><script>alert(1)</script></foreignObject></svg>');
    expect($out)->not->toContain('<foreignobject')->not->toContain('<script');
});

it('drops a style attribute that hides a full-page overlay or an external/executable reference', function () {
    expect(HtmlSanitizer::clean('<p style="position:fixed;top:0">x</p>'))->not->toContain('style');
    expect(HtmlSanitizer::clean('<p style="POSITION: ABSOLUTE; left:0">x</p>'))->not->toContain('style');
    expect(HtmlSanitizer::clean('<p style="background:url(javascript:alert(1))">x</p>'))->not->toContain('style');
    expect(HtmlSanitizer::clean('<p style="width:expression(alert(1))">x</p>'))->not->toContain('style');
    expect(HtmlSanitizer::clean('<p style="behavior:url(x.htc)">x</p>'))->not->toContain('style');
    expect(HtmlSanitizer::clean('<p style="-moz-binding:url(x.xml)">x</p>'))->not->toContain('style');
});

it('keeps a safe style attribute such as text alignment', function () {
    $out = HtmlSanitizer::clean('<p style="text-align:center">x</p>');
    expect($out)->toContain('style="text-align:center"');
});
