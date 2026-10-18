<?php

namespace App\Support\Transfer;

use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Upserts categories (an arbitrarily nested tree), articles, tags and media from a content
 * document. Shared by the wiki:import-docs command and the admin Transfer page.
 *
 * Accepted shapes: {"categories":[tree...],"articles":[...]} or the older single-root
 * {"category":{tree},"articles":[...]}.
 */
class DocsImporter
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /** @var array<string, Media> Cache of image name => Media row, valid for this instance's lifetime. */
    private array $mediaCache = [];

    /**
     * @param  callable(string): ?string  $resolveImage  image name => readable local file path, or null
     * @return array<string, mixed> stats
     */
    public function import(string $contentJson, callable $resolveImage, ?int $adminId, string $revisionNote): array
    {
        $data = json_decode($contentJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON: '.json_last_error_msg());
        }

        $stats = self::emptyStats();

        DB::transaction(function () use ($data, $resolveImage, $adminId, $revisionNote, &$stats) {
            $roots = $data['categories'] ?? (isset($data['category']) ? [$data['category']] : []);
            $byPath = [];
            $this->upsertTree($roots, null, '', $byPath, $stats);

            foreach (($data['articles'] ?? []) as $art) {
                $path = trim((string) ($art['category_path'] ?? ''), '/');
                if (! isset($byPath[$path])) {
                    throw new \RuntimeException("Article '".($art['slug'] ?? '?')."' references unknown category_path '$path'");
                }
                $this->upsertArticle($art, $byPath[$path], $resolveImage, $adminId, $revisionNote, $stats);
            }
        });

        return $stats;
    }

    public static function emptyStats(): array
    {
        return [
            'categories_created' => 0, 'categories_updated' => 0,
            'articles_created' => 0, 'articles_updated' => 0, 'articles_unchanged' => 0,
            'media_created' => 0, 'media_reused' => 0, 'media_updated' => 0,
            'images_dropped' => 0, 'missing_shots' => [],
        ];
    }

    /** @param  array<string, Category>  $byPath */
    private function upsertTree(array $nodes, ?Category $parent, string $prefix, array &$byPath, array &$stats): void
    {
        foreach (array_values($nodes) as $i => $node) {
            $category = $this->upsertCategory(
                name: $node['name'],
                slug: $node['slug'],
                description: $node['description'] ?? null,
                icon: $node['icon'] ?? null,
                parentId: $parent?->id,
                sortOrder: (int) ($node['sort_order'] ?? $i),
                stats: $stats,
            );
            $path = $prefix === '' ? $node['slug'] : $prefix.'/'.$node['slug'];
            $byPath[$path] = $category;
            $this->upsertTree($node['children'] ?? [], $category, $path, $byPath, $stats);
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

    private function upsertArticle(array $art, Category $category, callable $resolveImage, ?int $adminId, string $revisionNote, array &$stats): void
    {
        $body = $this->processShots($art['body_html'] ?? '', $resolveImage, $adminId, $stats);
        $cleanBody = HtmlSanitizer::clean($body);
        $excerpt = trim((string) ($art['excerpt'] ?? '')) !== '' ? $art['excerpt'] : HtmlSanitizer::excerpt($cleanBody);

        // Identity is (category_id, slug) only. Slugs such as "common-errors" legitimately
        // repeat across channel categories, so a bare-slug fallback would move articles
        // between categories instead of creating them.
        $article = Article::where('category_id', $category->id)->where('slug', $art['slug'])->first();
        $isNew = $article === null;

        $attrs = [
            'category_id' => $category->id,
            'title' => $art['title'],
            'excerpt' => $excerpt,
            'body_html' => $cleanBody,
            'body_text' => HtmlSanitizer::toText($cleanBody),
            'status' => in_array($art['status'] ?? 'published', ['published', 'draft'], true) ? ($art['status'] ?? 'published') : 'published',
            'sort_order' => (int) ($art['sort_order'] ?? 0),
            'seo_title' => $art['seo_title'] ?? null,
            'meta_description' => $art['meta_description'] ?? null,
            'updated_by' => $adminId,
        ];
        // Optional fields only touch the article when the source document carries them.
        if (array_key_exists('canonical_url', $art)) {
            $attrs['canonical_url'] = $art['canonical_url'];
        }
        if (array_key_exists('noindex', $art)) {
            $attrs['noindex'] = (bool) $art['noindex'];
        }
        if (! empty($art['og_image'])) {
            $ogPath = $resolveImage($art['og_image']);
            if ($ogPath !== null && is_file($ogPath)) {
                $attrs['og_image_id'] = $this->mediaFor($art['og_image'], $ogPath, null, $adminId, $stats)->id;
            }
        }

        $givenPublishedAt = ! empty($art['published_at']) ? Carbon::parse($art['published_at']) : null;
        $hasPublishedAt = array_key_exists('published_at', $art);

        if ($isNew) {
            $attrs['slug'] = $art['slug'];
            $attrs['created_by'] = $adminId;
            $attrs['published_at'] = $givenPublishedAt ?? ($attrs['status'] === 'published' ? now() : null);
            $article = Article::create($attrs);
            $stats['articles_created']++;
            $this->createRevision($article, $adminId, $revisionNote);
        } else {
            $contentFields = ['title', 'body_html', 'excerpt', 'seo_title', 'meta_description'];
            $contentChanged = collect($contentFields)->contains(fn ($f) => $attrs[$f] !== $article->$f);
            $anyChanged = collect($attrs)->contains(fn ($v, $k) => $v !== $article->$k);

            if ($hasPublishedAt) {
                $same = $givenPublishedAt === null ? $article->published_at === null : ($article->published_at?->eq($givenPublishedAt) ?? false);
                if (! $same) {
                    $attrs['published_at'] = $givenPublishedAt;
                    $anyChanged = true;
                }
            } elseif (is_null($article->published_at) && $attrs['status'] === 'published') {
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

    /** Replace `{{shot:NAME}}` image tags with real media URLs, dropping ones whose file is missing. */
    private function processShots(string $body, callable $resolveImage, ?int $adminId, array &$stats): string
    {
        return preg_replace_callback('/<img\b[^>]*>/i', function (array $m) use ($resolveImage, $adminId, &$stats) {
            $tag = $m[0];
            if (! preg_match('/\bsrc="\{\{shot:([^"}]+\.(?:png|jpe?g|gif|webp))\}\}"/i', $tag, $sm)) {
                return $tag;
            }
            $name = $sm[1];
            $path = $resolveImage($name);
            if ($path === null || ! is_file($path)) {
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
        $mime = $size['mime'] ?? 'image/png';
        $ext = self::MIME_EXTENSIONS[$mime] ?? 'png';

        $media = Media::where('original_name', $name)->first();
        if ($media) {
            $current = Storage::disk('public')->exists($media->disk_path) ? Storage::disk('public')->get($media->disk_path) : null;
            if ($current === null || md5($current) !== md5($bytes)) {
                Storage::disk('public')->put($media->disk_path, $bytes);
                $media->update(['mime' => $mime, 'size' => strlen($bytes), 'width' => $width, 'height' => $height]);
                $stats['media_updated']++;
            } else {
                $stats['media_reused']++;
            }
        } else {
            $diskPath = 'media/'.now()->format('Y/m').'/'.Str::random(16).'.'.$ext;
            Storage::disk('public')->put($diskPath, $bytes);
            $media = Media::create([
                'disk_path' => $diskPath, 'original_name' => $name, 'mime' => $mime, 'size' => strlen($bytes),
                'width' => $width, 'height' => $height, 'alt' => $alt, 'uploaded_by' => $adminId,
            ]);
            $stats['media_created']++;
        }

        return $this->mediaCache[$name] = $media;
    }
}
