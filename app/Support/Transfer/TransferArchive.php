<?php

namespace App\Support\Transfer;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/** Safely reads a transfer zip and feeds it to DocsImporter. */
class TransferArchive
{
    private const MAX_CONTENT_BYTES = 100 * 1024 * 1024;

    private const MAX_IMAGE_BYTES = 50 * 1024 * 1024;

    private const IMAGE_MIMES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    /** @throws \InvalidArgumentException when the archive is not a usable transfer file */
    public function import(string $zipPath, ?int $adminId, string $revisionNote = 'Imported from transfer'): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \InvalidArgumentException('That file is not a readable zip archive.');
        }

        $tmp = DocsExporter::transferDir().'/import-'.Str::random(12);
        try {
            File::ensureDirectoryExists($tmp);

            $stat = $zip->statName('content.json');
            if ($stat === false || $stat['size'] > self::MAX_CONTENT_BYTES) {
                throw new \InvalidArgumentException('The zip does not contain a valid content.json.');
            }
            $content = $zip->getFromName('content.json');
            $decoded = json_decode((string) $content, true);
            if (! is_array($decoded) || ($decoded['format'] ?? null) !== DocsExporter::FORMAT) {
                throw new \InvalidArgumentException('content.json is not a Laradoc transfer file.');
            }

            /** @var array<string, string> image name => extracted file */
            $images = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if ($entry === false || ! str_starts_with($entry, 'media/')) {
                    continue;
                }
                $name = substr($entry, 6);
                if ($name === '' || str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, '..')
                    || str_contains($name, "\0") || str_starts_with($name, '.')) {
                    continue;
                }
                $in = $zip->getStream($entry);
                if ($in === false) {
                    continue;
                }
                $dest = $tmp.'/'.md5($name);
                $out = fopen($dest, 'wb');
                $written = 0;
                while (! feof($in) && ($chunk = fread($in, 65536)) !== false && $chunk !== '') {
                    $written += strlen($chunk);
                    if ($written > self::MAX_IMAGE_BYTES) {
                        break;
                    }
                    fwrite($out, $chunk);
                }
                fclose($in);
                fclose($out);

                $info = $written <= self::MAX_IMAGE_BYTES ? @getimagesize($dest) : false;
                if ($info && in_array($info['mime'] ?? '', self::IMAGE_MIMES, true)) {
                    $images[$name] = $dest;
                } else {
                    @unlink($dest);
                }
            }
            $zip->close();

            return (new DocsImporter)->import(
                (string) $content,
                fn (string $name) => $images[$name] ?? null,
                $adminId,
                $revisionNote,
            );
        } finally {
            File::deleteDirectory($tmp);
        }
    }
}
