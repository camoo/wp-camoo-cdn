<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class FileList
{
    public function get(string $directory, ?string $pattern = null): Generator
    {
        $pattern = $pattern ?? WP_CAMOO_CDN_STATIC_FILES_PATTERN;
        $directories = new RecursiveDirectoryIterator($directory);
        $iterator = new RecursiveIteratorIterator($directories);

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();
            if (preg_match($pattern, $filePath)) {
                yield $filePath => $file->getMTime();
            }
        }
    }
}
