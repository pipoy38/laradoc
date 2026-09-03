<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WikiImportDocs extends Command
{
    protected $signature = 'wiki:import-docs {dir : Path to the docs job directory (containing content/ and erp-shots/)}
        {--order= : Comma-separated root category slugs, in display order}
        {--replace-sample : Delete the seeded getting-started/welcome sample article before importing}';

    protected $description = 'Bulk-import documentation categories and articles from a docs-job export';

    private const DEFAULT_ROOT_ORDER = ['getting-started', 'shopee', 'lazada', 'tiktok-shop', 'working-in-the-erp'];

    /** @var array<string, Media> Cache of shot filename => Media row, valid for the whole run. */
    private array $mediaCache = [];

    public function handle(): int
    {
        $dir = rtrim((string) $this->argument('dir'), '/');
        $contentDir = "$dir/content";
        $shotsDir = "$dir/erp-shots";

        if (! is_dir($contentDir)) {
            $this->error("Content directory not found: $contentDir");

            return self::FAILURE;
        }

        $files = collect(glob("$contentDir/*.json") ?: [])->sort()->values();
        if ($files->isEmpty()) {
            $this->error("No content JSON files found in $contentDir");

            return self::FAILURE;
        }

        // Decode and validate every file up front, so a single bad file aborts the whole
        // run before any database writes happen (no partial import).
        $decoded = [];
        foreach ($files as $file) {
            $json = file_get_contents($file);
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Invalid JSON in $file: ".json_last_error_msg());

                return self::FAILURE;
            }
            $decoded[] = ['file' => $file, 'data' => $data];
        }

        $adminId = User::where('role', 'admin')->oldest('id')->value('id');

        $stats = [
            'categories_created' => 0, 'categories_updated' => 0,
            'articles_created' => 0, 'articles_updated' => 0, 'articles_unchanged' => 0,
            'media_created' => 0, 'media_reused' => 0, 'media_updated' => 0,
            'images_dropped' => 0, 'missing_shots' => [],
        ];

        if ($this->option('replace-sample')) {
            $sample = Article::whereHas('category', fn ($q) => $q->where('slug', 'getting-started')->whereNull('parent_id'))
                ->where('slug', 'welcome')->first();
            $sample?->delete();
        }

        $rootOrder = $this->rootOrder($decoded);

        foreach ($decoded as ['file' => $file, 'data' => $data]) {
            try {
                DB::transaction(function () use ($data, $shotsDir, $adminId, $rootOrder, &$stats) {
                    $this->importFile($data, $shotsDir, $adminId, $rootOrder, $stats);
                });
            } catch (\Throwable $e) {
                $this->error("Failed importing $file: ".$e->getMessage());

                return self::FAILURE;
            }
        }

        $this->printSummary($stats);

        return self::SUCCESS;
    }

    /**
     * Roots are ordered per --order (or the default list); any root slug encountered that
     * isn't in that list falls back to alphabetical order, appended after the known ones.
     *
     * @param  array<int, array{file: string, data: array}>  $decoded
     * @return array<string, int> slug => sort_order
     */
    private function rootOrder(array $decoded): array
    {
        $orderOption = $this->option('order');
        $known = $orderOption !== null
            ? array_values(array_filter(array_map('trim', explode(',', $orderOption))))
            : self::DEFAULT_ROOT_ORDER;

        $seen = collect($decoded)->pluck('data.category.slug')->filter()->unique()->values();
        $unknown = $seen->diff($known)->sort()->values();
        $final = collect($known)->filter(fn ($slug) => $seen->contains($slug))->values()->concat($unknown);

        return $final->flip()->all();
    }

    private function importFile(array $data, string $shotsDir, ?int $adminId, array $rootOrder, array &$stats): void
    {
        $categoryData = $data['category'];
        $rootSlug = $categoryData['slug'];
        $root = $this->upsertCategory(
            name: $categoryData['name'],
            slug: $rootSlug,
            description: $categoryData['description'] ?? null,
            icon: $categoryData['icon'] ?? null,
            parentId: null,
            sortOrder: $rootOrder[$rootSlug] ?? 0,
            stats: $stats,
        );

        $categoriesByPath = [$rootSlug => $root];
        foreach (($categoryData['children'] ?? []) as $i => $child) {
            $childCategory = $this->upsertCategory(
                name: $child['name'],
                slug: $child['slug'],
                description: $child['description'] ?? null,
                icon: null,
                parentId: $root->id,
                sortOrder: $i,
                stats: $stats,
            );
            $categoriesByPath["$rootSlug/{$child['slug']}"] = $childCategory;
        }

        foreach (($data['articles'] ?? []) as $art) {
            $path = $art['category_path'];
            if (! isset($categoriesByPath[$path])) {
                throw new \RuntimeException("Article '{$art['slug']}' references unknown category_path '$path'");
            }
            $this->upsertArticle($art, $categoriesByPath[$path], $shotsDir, $adminId, $stats);
        }
    }

    private function upsertCategory(string $name, string $slug, ?string $description, ?string $icon, ?int $parentId, int $sortOrder, array &$stats): Category
    {
        $category = Category::where('slug', $slug)->where('parent_id', $parentId)->first();
        $attrs = ['name' => $name, 'description' => $description, 'icon' => $icon, 'sort_order' => $sortOrder];

        if ($category) {
            $category->update($attrs);
            $stats['categories_updated']++;
        } else {
            $category = Category::create($attrs + ['slug' => $slug, 'parent_id' => $parentId]);
            $stats['categories_created']++;
        }

        return $category;
    }

    private function upsertArticle(array $art, Category $category, string $shotsDir, ?int $adminId, array &$stats): void
    {
        $body = $this->processShots($art['body_html'], $shotsDir, $adminId, $stats);
        $cleanBody = HtmlSanitizer::clean($body);
        $excerpt = trim((string) ($art['excerpt'] ?? '')) !== '' ? $art['excerpt'] : HtmlSanitizer::excerpt($cleanBody);
        $bodyText = HtmlSanitizer::toText($cleanBody);

        // Primary key is (category_id, slug); if that misses, fall back to a bare slug
        // lookup so an article that moved category between runs is updated, not duplicated.
        $article = Article::where('category_id', $category->id)->where('slug', $art['slug'])->first()
            ?? Article::where('slug', $art['slug'])->first();
        $isNew = $article === null;

        $attrs = [
            'category_id' => $category->id,
            'title' => $art['title'],
            'excerpt' => $excerpt,
            'body_html' => $cleanBody,
            'body_text' => $bodyText,
            'status' => 'published',
            'sort_order' => $art['sort_order'] ?? 0,
            'seo_title' => $art['seo_title'] ?? null,
            'meta_description' => $art['meta_description'] ?? null,
            'updated_by' => $adminId,
        ];

        if ($isNew) {
            $attrs['slug'] = $art['slug'];
            $attrs['created_by'] = $adminId;
            $attrs['published_at'] = now();
            $article = Article::create($attrs);
            $stats['articles_created']++;
            $this->createRevision($article, $adminId, 'Imported from docs-job');
        } else {
            $contentFields = ['title', 'body_html', 'excerpt', 'seo_title', 'meta_description'];
            $contentChanged = collect($contentFields)->contains(fn ($f) => $attrs[$f] !== $article->$f);
            $anyChanged = collect($attrs)->contains(fn ($v, $k) => $v !== $article->$k);
            if (is_null($article->published_at)) {
                $attrs['published_at'] = now();
                $anyChanged = true;
            }

            if ($anyChanged) {
                $article->update($attrs);
                $stats['articles_updated']++;
            } else {
                $stats['articles_unchanged']++;
            }
            if ($contentChanged) {
                $this->createRevision($article, $adminId, 'Re-imported');
            }
        }

        $tagIds = collect($art['tags'] ?? [])->map(function (string $tagName) {
            return Tag::firstOrCreate(['slug' => Str::slug($tagName)], ['name' => $tagName])->id;
        });
        $article->tags()->sync($tagIds);
    }

    private function createRevision(Article $article, ?int $adminId, string $note): void
    {
        $article->revisions()->create([
            'user_id' => $adminId,
            'title' => $article->title,
            'body_html' => $article->body_html,
            'excerpt' => $article->excerpt,
            'seo_title' => $article->seo_title,
            'meta_description' => $article->meta_description,
            'change_note' => $note,
            'created_at' => now(),
        ]);
    }

    /** Replace `{{shot:NAME.png}}` image tags with real media URLs, dropping ones whose file is missing. */
    private function processShots(string $body, string $shotsDir, ?int $adminId, array &$stats): string
    {
        return preg_replace_callback('/<img\b[^>]*>/i', function (array $m) use ($shotsDir, $adminId, &$stats) {
            $tag = $m[0];
            if (! preg_match('/\bsrc="\{\{shot:([^"}]+\.png)\}\}"/i', $tag, $sm)) {
                return $tag;
            }
            $name = $sm[1];
            $path = "$shotsDir/$name";
            if (! is_file($path)) {
                $stats['missing_shots'][$name] = true;
                $stats['images_dropped']++;

                return '';
            }
            $alt = null;
            if (preg_match('/\balt="([^"]*)"/i', $tag, $am)) {
                $alt = html_entity_decode($am[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            $media = $this->mediaFor($name, $path, $alt, $adminId, $stats);

            return str_replace('{{shot:'.$name.'}}', $media->url(), $tag);
        }, $body) ?? $body;
    }

    private function mediaFor(string $name, string $path, ?string $alt, ?int $adminId, array &$stats): Media
    {
        if (isset($this->mediaCache[$name])) {
            return $this->mediaCache[$name];
        }

        $bytes = file_get_contents($path);
        $size = @getimagesize($path);
        [$width, $height] = $size ? [$size[0], $size[1]] : [null, null];

        $media = Media::where('original_name', $name)->first();
        if ($media) {
            $current = Storage::disk('public')->exists($media->disk_path) ? Storage::disk('public')->get($media->disk_path) : null;
            if ($current === null || md5($current) !== md5($bytes)) {
                Storage::disk('public')->put($media->disk_path, $bytes);
                $media->update(['mime' => 'image/png', 'size' => strlen($bytes), 'width' => $width, 'height' => $height]);
                $stats['media_updated']++;
            } else {
                $stats['media_reused']++;
            }
        } else {
            $diskDir = 'media/'.now()->format('Y/m');
            $diskPath = $diskDir.'/'.Str::random(16).'.png';
            Storage::disk('public')->put($diskPath, $bytes);
            $media = Media::create([
                'disk_path' => $diskPath, 'original_name' => $name, 'mime' => 'image/png', 'size' => strlen($bytes),
                'width' => $width, 'height' => $height, 'alt' => $alt, 'uploaded_by' => $adminId,
            ]);
            $stats['media_created']++;
        }

        return $this->mediaCache[$name] = $media;
    }

    private function printSummary(array $stats): void
    {
        $this->table(['Metric', 'Count'], [
            ['Categories created', $stats['categories_created']],
            ['Categories updated', $stats['categories_updated']],
            ['Articles created', $stats['articles_created']],
            ['Articles updated', $stats['articles_updated']],
            ['Articles unchanged', $stats['articles_unchanged']],
            ['Media created', $stats['media_created']],
            ['Media reused', $stats['media_reused']],
            ['Media updated (bytes changed)', $stats['media_updated']],
            ['Images dropped (missing file)', $stats['images_dropped']],
        ]);

        $missing = array_keys($stats['missing_shots']);
        if ($missing !== []) {
            $this->warn('Referenced but missing shot files: '.implode(', ', $missing));
        }
    }
}
