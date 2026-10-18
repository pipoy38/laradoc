<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\PathRedirect;
use App\Models\Tag;
use App\Models\User;
use App\Support\Settings;
use App\Support\Transfer\DocsImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WikiImportDocs extends Command
{
    protected $signature = 'wiki:import-docs {dir : Path to the docs job directory (containing content/ and erp-shots/)}
        {--order= : Comma-separated root category slugs, in display order}
        {--replace-sample : Delete the seeded getting-started/welcome sample article before importing}
        {--shots= : Sub-directory of the job directory holding the PNGs (default erp-shots)}
        {--purge : Delete every existing article, category, tag and media row (and media files) before importing}';

    protected $description = 'Bulk-import documentation categories and articles from a docs-job export';

    private const DEFAULT_ROOT_ORDER = ['getting-started', 'shopee', 'lazada', 'tiktok-shop', 'working-in-the-erp'];

    private DocsImporter $importer;

    public function handle(): int
    {
        $this->importer = new DocsImporter;

        $dir = rtrim((string) $this->argument('dir'), '/');
        $contentDir = "$dir/content";
        $shotsDir = $dir.'/'.($this->option('shots') ?: 'erp-shots');

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

        $stats = DocsImporter::emptyStats();

        if ($this->option('purge')) {
            $this->purgeExisting();
        }

        if ($this->option('replace-sample')) {
            $sample = Article::whereHas('category', fn ($q) => $q->where('slug', 'getting-started')->whereNull('parent_id'))
                ->where('slug', 'welcome')->first();
            $sample?->delete();
        }

        $rootOrder = $this->rootOrder($decoded);

        foreach ($decoded as ['file' => $file, 'data' => $data]) {
            try {
                $this->importFile($data, $shotsDir, $adminId, $rootOrder, $stats);
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

    /** Convert one docs-job file to the importer's tree format (root order from --order, children by position). */
    private function importFile(array $data, string $shotsDir, ?int $adminId, array $rootOrder, array &$stats): void
    {
        $root = $data['category'];
        $root['sort_order'] = $rootOrder[$root['slug']] ?? 0;
        $root['children'] = collect($root['children'] ?? [])->values()
            ->map(fn ($child, $i) => ['icon' => null, 'sort_order' => $i] + $child)->all();

        $json = json_encode(['categories' => [$root], 'articles' => $data['articles'] ?? []]);
        $resolve = fn (string $name) => is_file("$shotsDir/$name") ? "$shotsDir/$name" : null;

        $fileStats = $this->importer->import($json, $resolve, $adminId, 'Imported from docs-job');
        foreach ($fileStats as $key => $value) {
            $stats[$key] += $value;
        }
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

    /** Remove every article, category, tag and media row so the import is the whole site. */
    private function purgeExisting(): void
    {
        $counts = ['articles' => Article::count(), 'categories' => Category::count(), 'media' => Media::count()];
        DB::transaction(function () {
            Article::query()->each(fn (Article $a) => $a->delete());
            Tag::query()->delete();
            Media::query()->each(function (Media $m) {
                Storage::disk('public')->delete($m->disk_path);
                $m->delete();
            });
            // children before parents; parent_id is nullOnDelete so order only matters for tidiness
            Category::query()->orderByDesc('parent_id')->each(fn (Category $c) => $c->delete());
            PathRedirect::query()->delete();
        });
        app(Settings::class)->setMany(['logo_media_id' => null, 'favicon_media_id' => null]);
        $this->line(sprintf('Purged %d articles, %d categories, %d media files.', $counts['articles'], $counts['categories'], $counts['media']));
    }
}
