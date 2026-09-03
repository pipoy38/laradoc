<?php
use App\Support\Toc;

it('adds unique ids to h2/h3 and lists them', function () {
    $r = Toc::build('<h2>Install</h2><p>x</p><h3>Install</h3><h2 id="keep">Keep</h2><h4>Skip me</h4>');
    expect($r['items'])->toBe([['id' => 'install', 'text' => 'Install', 'level' => 2], ['id' => 'install-2', 'text' => 'Install', 'level' => 3], ['id' => 'keep', 'text' => 'Keep', 'level' => 2]])
        ->and($r['html'])->toContain('<h2 id="install">')->toContain('<h3 id="install-2">')->toContain('<h4>Skip me</h4>');
});
