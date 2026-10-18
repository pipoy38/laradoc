<?php

namespace App\Support\Transfer;

use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/** Builds a transfer zip: content.json plus the referenced images under media/. */
class DocsExporter
{
    public const FORMAT = 'laradoc-transfer';

    /** @var array<int, string> media id => unique export name */
    private array $names = [];

    /** @var array<string, true> lowercase names already taken */
    private array $taken = [];

    /** @var array<string, Media> disk_path => Media */
    private array $byPath = [];

    /** @var array<string, string> export name => disk_path, for images to put in the zip */
    private array $files = [];

    public static function transferDir(): string
    {
        $dir = storage_path('app/transfer');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    /** @return string path of the temporary zip; the caller deletes it */
    public function build(): string
    {
        $this->byPath = Media::all()->keyBy('disk_path')->all();

        $categories = Category::all();
        $paths = [];
        $tree = $this->categoryTree($categories, null, '', $paths);

        $articles = [];
        Article::with('tags')->orderBy('id')->chunkById(200, function ($chunk) use (&$articles, $paths) {
            foreach ($chunk as $a) {
                $articles[] = $this->articleRow($a, $paths[$a->category_id] ?? null);
            }
        });
        $articles = array_values(array_filter($articles));
        usort($articles, fn ($x, $y) => [$x['category_path'], $x['sort_order'], $x['title']] <=> [$y['category_path'], $y['sort_order'], $y['title']]);

        $content = [
            'format' => self::FORMAT,
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'site' => ['name' => setting('site_name', 'Laradoc')],
            'categories' => $tree,
            'articles' => $articles,
        ];

        $zipPath = tempnam(self::transferDir(), 'export-');
        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create the export archive.');
        }
        $zip->addFromString('content.json', json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $disk = Storage::disk('public');
        foreach ($this->files as $name => $diskPath) {
            if (! $disk->exists($diskPath)) {
                continue;
            }
            // addFile reads lazily at close(), so large images never sit in memory together.
            $zip->addFile($disk->path($diskPath), 'media/'.$name);
            $zip->setCompressionName('media/'.$name, \ZipArchive::CM_STORE);
        }
        $zip->close();

        return $zipPath;
    }

    private function categoryTree($all, ?int $parentId, string $prefix, array &$paths): array
    {
        $out = [];
        $level = $all->where('parent_id', $parentId)->sortBy([['sort_order', 'asc'], ['name', 'asc']]);
        foreach ($level as $c) {
            $path = $prefix === '' ? $c->slug : $prefix.'/'.$c->slug;
            $paths[$c->id] = $path;
            $out[] = [
                'name' => $c->name, 'slug' => $c->slug, 'icon' => $c->icon, 'description' => $c->description,
                'sort_order' => $c->sort_order,
                'children' => $this->categoryTree($all, $c->id, $path, $paths),
            ];
        }

        return $out;
    }

    private function articleRow(Article $a, ?string $categoryPath): ?array
    {
        if ($categoryPath === null) {
            return null;
        }
        $row = [
            'title' => $a->title, 'slug' => $a->slug, 'category_path' => $categoryPath, 'sort_order' => $a->sort_order,
            'status' => $a->status, 'published_at' => $a->published_at?->toIso8601String(),
            'excerpt' => $a->excerpt, 'tags' => $a->tags->pluck('name')->values()->all(),
            'seo_title' => $a->seo_title, 'meta_description' => $a->meta_description,
            'canonical_url' => $a->canonical_url, 'noindex' => (bool) $a->noindex,
            'body_html' => $this->rewriteImages((string) $a->body_html),
        ];
        $og = $a->og_image_id ? Media::find($a->og_image_id) : null;
        if ($og) {
            $row['og_image'] = $this->nameFor($og);
        }

        return $row;
    }

    /** Turn /storage/media/... image sources into {{shot:NAME}} placeholders. */
    private function rewriteImages(string $html): string
    {
        return preg_replace_callback('/<img\b[^>]*>/i', function (array $m) {
            return preg_replace_callback(
                '/\bsrc=(["\'])(?:https?:\/\/[^\/"\']+)?\/storage\/(media\/[^"\'?#]+)(?:[?#][^"\']*)?\1/i',
                function (array $s) {
                    $media = $this->byPath[rawurldecode($s[2])] ?? null;

                    return $media ? 'src="{{shot:'.$this->nameFor($media).'}}"' : $s[0];
                },
                $m[0],
            );
        }, $html) ?? $html;
    }

    private function nameFor(Media $media): string
    {
        if (isset($this->names[$media->id])) {
            return $this->names[$media->id];
        }

        $base = basename(str_replace('\\', '/', (string) ($media->original_name ?: $media->disk_path)));
        $base = preg_replace('/[^\p{L}\p{N}._() -]+/u', '_', $base);
        $base = ltrim($base, '.');
        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            $ext = pathinfo($media->disk_path, PATHINFO_EXTENSION) ?: 'png';
            $base = ($base === '' ? 'image' : $base).'.'.$ext;
        }
        $stem = pathinfo($base, PATHINFO_FILENAME);
        $name = $base;
        for ($n = 2; isset($this->taken[strtolower($name)]); $n++) {
            $name = "$stem-$n.".pathinfo($base, PATHINFO_EXTENSION);
        }
        $this->taken[strtolower($name)] = true;
        $this->files[$name] = $media->disk_path;

        return $this->names[$media->id] = $name;
    }
}
