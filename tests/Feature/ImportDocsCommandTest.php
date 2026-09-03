<?php

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\SampleContentSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create(['role' => 'admin']);
    // Seed the sample "getting-started / welcome" article so --replace-sample has something to remove.
    (new SampleContentSeeder)->run();
});

function docsJobDir(string $body1, string $body2 = '<p>Second article body.</p>'): string
{
    $dir = sys_get_temp_dir().'/docs-job-test-'.Str::random(8);
    mkdir("$dir/content", 0777, true);
    mkdir("$dir/erp-shots", 0777, true);

    $im = imagecreatetruecolor(4, 4);
    imagepng($im, "$dir/erp-shots/a.png");
    imagedestroy($im);

    $content = [
        'category' => [
            'name' => 'Getting started',
            'slug' => 'getting-started',
            'icon' => '🚀',
            'description' => 'How to use this documentation site.',
            'children' => [
                ['name' => 'Setup', 'slug' => 'setup', 'description' => 'Initial setup steps.'],
            ],
        ],
        'articles' => [
            [
                'title' => 'First article',
                'slug' => 'first-article',
                'category_path' => 'getting-started/setup',
                'sort_order' => 0,
                'excerpt' => 'An excerpt for the first article.',
                'tags' => ['setup', 'guide'],
                'seo_title' => 'First article SEO title',
                'meta_description' => 'First article meta description.',
                'body_html' => $body1,
            ],
            [
                'title' => 'Second article',
                'slug' => 'second-article',
                'category_path' => 'getting-started/setup',
                'sort_order' => 1,
                'excerpt' => 'An excerpt for the second article.',
                'tags' => ['guide'],
                'seo_title' => null,
                'meta_description' => 'Second article meta description.',
                'body_html' => $body2,
            ],
        ],
    ];

    file_put_contents("$dir/content/getting-started.json", json_encode($content, JSON_PRETTY_PRINT));

    return $dir;
}

it('imports categories, articles, tags, revisions and media idempotently', function () {
    $body = '<h2>Setup</h2><p>Do the thing.</p>'
        .'<img src="{{shot:a.png}}" alt="A screenshot"><img src="{{shot:missing.png}}" alt="A missing screenshot">';
    $dir = docsJobDir($body);

    $this->artisan('wiki:import-docs', ['dir' => $dir, '--replace-sample' => true])->assertExitCode(0);

    expect(Article::where('slug', 'welcome')->exists())->toBeFalse();

    $root = Category::whereNull('parent_id')->where('slug', 'getting-started')->first();
    expect($root)->not->toBeNull()->and($root->name)->toBe('Getting started');
    $child = Category::where('parent_id', $root->id)->where('slug', 'setup')->first();
    expect($child)->not->toBeNull();

    expect(Article::count())->toBe(2);
    $first = Article::where('slug', 'first-article')->first();
    expect($first)->not->toBeNull()
        ->and($first->category_id)->toBe($child->id)
        ->and($first->status)->toBe('published')
        ->and($first->published_at)->not->toBeNull()
        ->and($first->created_by)->toBe($this->admin->id)
        ->and($first->updated_by)->toBe($this->admin->id);

    expect(Tag::pluck('slug')->sort()->values()->all())->toBe(['guide', 'setup']);
    expect($first->tags()->pluck('slug')->sort()->values()->all())->toBe(['guide', 'setup']);

    expect(Media::count())->toBe(1);
    $media = Media::first();
    expect($media->original_name)->toBe('a.png')
        ->and($media->alt)->toBe('A screenshot')
        ->and($media->mime)->toBe('image/png')
        ->and($media->uploaded_by)->toBe($this->admin->id);
    Storage::disk('public')->assertExists($media->disk_path);

    expect($first->body_html)->toContain($media->url())
        ->and($first->body_html)->not->toContain('{{shot:')
        ->and($first->body_html)->not->toContain('missing.png')
        ->and(substr_count($first->body_html, '<img'))->toBe(1);

    expect(ArticleRevision::count())->toBe(2);
    $rev = ArticleRevision::where('article_id', $first->id)->first();
    expect($rev->change_note)->toBe('Imported from docs-job')->and($rev->user_id)->toBe($this->admin->id);

    // Second run: fully idempotent, nothing new created.
    $catCount = Category::count();
    $artCount = Article::count();
    $tagCount = Tag::count();
    $mediaCount = Media::count();
    $revCount = ArticleRevision::count();

    $this->artisan('wiki:import-docs', ['dir' => $dir, '--replace-sample' => true])->assertExitCode(0);

    expect(Category::count())->toBe($catCount)
        ->and(Article::count())->toBe($artCount)
        ->and(Tag::count())->toBe($tagCount)
        ->and(Media::count())->toBe($mediaCount)
        ->and(ArticleRevision::count())->toBe($revCount);

    // Third run with a changed body: one new revision with a "Re-imported" note, no new article.
    file_put_contents(
        "$dir/content/getting-started.json",
        str_replace('Do the thing.', 'Do the thing, updated.', file_get_contents("$dir/content/getting-started.json"))
    );

    $this->artisan('wiki:import-docs', ['dir' => $dir, '--replace-sample' => true])->assertExitCode(0);

    expect(Article::count())->toBe($artCount)
        ->and(ArticleRevision::count())->toBe($revCount + 1);

    $latestRev = ArticleRevision::where('article_id', $first->id)->latest('id')->first();
    expect($latestRev->change_note)->toBe('Re-imported');
    expect($first->fresh()->body_html)->toContain('Do the thing, updated.');
});

it('fails fast with no partial writes on invalid JSON', function () {
    $dir = sys_get_temp_dir().'/docs-job-bad-'.Str::random(8);
    mkdir("$dir/content", 0777, true);
    mkdir("$dir/erp-shots", 0777, true);
    file_put_contents("$dir/content/broken.json", '{not valid json');

    $this->artisan('wiki:import-docs', ['dir' => $dir])->assertExitCode(1);

    expect(Category::whereNull('parent_id')->where('slug', '!=', 'getting-started')->count())->toBe(0)
        ->and(Article::count())->toBe(1); // only the untouched sample article
});
