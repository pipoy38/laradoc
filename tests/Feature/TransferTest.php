<?php

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($this->admin);
});

function pngBytes(): string
{
    $im = imagecreatetruecolor(4, 4);
    ob_start();
    imagepng($im);

    return ob_get_clean();
}

function seedTransferContent(): void
{
    $root = Category::create(['name' => 'Guides', 'slug' => 'guides', 'icon' => 'book', 'description' => 'Root', 'sort_order' => 1]);
    $child = Category::create(['name' => 'Setup', 'slug' => 'setup', 'parent_id' => $root->id, 'sort_order' => 2]);

    Storage::disk('public')->put('media/2026/10/abc123.png', pngBytes());
    $media = Media::create(['disk_path' => 'media/2026/10/abc123.png', 'original_name' => 'shot.png', 'mime' => 'image/png', 'size' => 100, 'width' => 4, 'height' => 4, 'uploaded_by' => auth()->id()]);

    $a = Article::create(['category_id' => $child->id, 'title' => 'First', 'slug' => 'first', 'excerpt' => 'Ex', 'status' => 'published', 'published_at' => now()->subDay(),
        'body_html' => '<p>Hi</p><img src="'.$media->url().'" alt="Shot">', 'body_text' => 'Hi', 'seo_title' => 'SEO', 'meta_description' => 'Meta',
        'canonical_url' => 'https://example.com/first', 'noindex' => true, 'og_image_id' => $media->id]);
    $a->tags()->sync([Tag::create(['name' => 'Setup', 'slug' => 'setup'])->id, Tag::create(['name' => 'Guide', 'slug' => 'guide'])->id]);
    Article::create(['category_id' => $root->id, 'title' => 'Second', 'slug' => 'second', 'excerpt' => 'Ex2', 'status' => 'published', 'published_at' => now(),
        'body_html' => '<p>Two</p>', 'body_text' => 'Two']);
}

function exportZipBytes($test): string
{
    $response = $test->post('/admin/transfer/export')->assertOk();
    $file = $response->baseResponse->getFile();
    $bytes = file_get_contents($file->getPathname());
    @unlink($file->getPathname());

    return $bytes;
}

function zipWith(array $entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'tz');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    foreach ($entries as $name => $content) {
        $zip->addFromString($name, $content);
    }
    $zip->close();
    $bytes = file_get_contents($path);
    unlink($path);

    return $bytes;
}

it('exports a zip with content.json and media', function () {
    seedTransferContent();
    $bytes = exportZipBytes($this);

    $path = tempnam(sys_get_temp_dir(), 'tz');
    file_put_contents($path, $bytes);
    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $json = json_decode($zip->getFromName('content.json'), true);

    expect($json['format'])->toBe('laradoc-transfer')
        ->and($json['articles'])->toHaveCount(2)
        ->and($json['categories'][0]['children'][0]['slug'])->toBe('setup');
    $first = collect($json['articles'])->firstWhere('slug', 'first');
    expect($first['body_html'])->toContain('{{shot:shot.png}}')->not->toContain('/storage/media')
        ->and($first['category_path'])->toBe('guides/setup')
        ->and($first['tags'])->toEqualCanonicalizing(['Setup', 'Guide'])
        ->and($first['og_image'])->toBe('shot.png');
    expect($zip->getFromName('media/shot.png'))->toBe(pngBytes());
    $zip->close();
    unlink($path);
});

it('round-trips content and is idempotent', function () {
    seedTransferContent();
    $bytes = exportZipBytes($this);

    DB::table('article_tag')->delete();
    ArticleRevision::query()->delete();
    Article::query()->delete();
    Category::query()->delete();
    Tag::query()->delete();
    Media::query()->delete();

    $upload = fn () => UploadedFile::fake()->createWithContent('export.zip', $bytes);
    $this->post('/admin/transfer/import', ['file' => $upload()])->assertRedirect('/admin/transfer')->assertSessionHas('ok');

    expect(Article::count())->toBe(2)->and(Category::count())->toBe(2)->and(Tag::count())->toBe(2)->and(Media::count())->toBe(1);
    $media = Media::first();
    Storage::disk('public')->assertExists($media->disk_path);
    $first = Article::where('slug', 'first')->first();
    expect($first->body_html)->toContain('/storage/media/')
        ->and($first->tags)->toHaveCount(2)
        ->and($first->noindex)->toBeTrue()
        ->and($first->canonical_url)->toBe('https://example.com/first')
        ->and($first->og_image_id)->toBe($media->id)
        ->and($first->category->parent->slug)->toBe('guides');
    expect(ArticleRevision::count())->toBe(2)
        ->and(ArticleRevision::pluck('change_note')->unique()->all())->toBe(['Imported from transfer']);

    $this->post('/admin/transfer/import', ['file' => $upload()])->assertSessionHas('ok', fn ($m) => str_contains($m, '0 created, 0 updated, 2 unchanged'));
    expect(Article::count())->toBe(2)->and(ArticleRevision::count())->toBe(2)->and(Media::count())->toBe(1);
});

it('refuses bad uploads and non-admins', function () {
    $this->post('/admin/transfer/import', ['file' => UploadedFile::fake()->createWithContent('x.zip', zipWith(['readme.txt' => 'hi']))])
        ->assertSessionHasErrors('file');
    $this->post('/admin/transfer/import', ['file' => UploadedFile::fake()->createWithContent('x.zip', zipWith(['content.json' => '{"format":"other"}']))])
        ->assertSessionHasErrors('file');
    $this->post('/admin/transfer/import', ['file' => UploadedFile::fake()->createWithContent('x.txt', 'not a zip')])
        ->assertSessionHasErrors('file');
    expect(Article::count())->toBe(0);

    $this->actingAs(User::factory()->create(['role' => 'editor']));
    $this->get('/admin/transfer')->assertForbidden();
});

it('ignores unsafe or non-image media entries', function () {
    $content = json_encode(['format' => 'laradoc-transfer', 'version' => 1, 'categories' => [['name' => 'A', 'slug' => 'a']],
        'articles' => [['title' => 'T', 'slug' => 't', 'category_path' => 'a', 'body_html' => '<p>x</p><img src="{{shot:evil.png}}" alt="e">']]]);
    $zip = zipWith(['content.json' => $content, 'media/evil.png' => '<?php echo 1;', 'media/../x.png' => pngBytes()]);
    $this->post('/admin/transfer/import', ['file' => UploadedFile::fake()->createWithContent('x.zip', $zip)])->assertSessionHas('ok');
    expect(Media::count())->toBe(0)->and(Article::first()->body_html)->not->toContain('<img');
});
