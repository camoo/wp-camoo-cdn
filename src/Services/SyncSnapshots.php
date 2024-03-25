<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

use Generator;
use WP_CAMOO\CDN\Dto\FileState;

final class SyncSnapshots
{
    public function compare(Generator $currentFilesGenerator): Generator
    {
        // Retrieve the previous snapshot from the database
        $previous_snapshot = get_option(WP_CAMOO_CDN_SNAPSHOT, []);
        $currentFiles = [];

        // Building current files array from the generator for comparison
        foreach ($currentFilesGenerator as $path => $mtime) {
            $currentFiles[$path] = $mtime;
            // Directly yield modified or new files
            if (isset($previous_snapshot[$path]) && $mtime > $previous_snapshot[$path]) {
                yield new FileState($path, $mtime, 'modified');
            } elseif (!isset($previous_snapshot[$path])) {
                yield new FileState($path, $mtime, 'new');
            }
        }

        // Identify and yield deleted files
        foreach (array_diff_key($previous_snapshot, $currentFiles) as $path => $mtime) {
            yield new FileState($path, $mtime, 'deleted');
        }
    }
}
