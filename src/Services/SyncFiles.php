<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

use WP_CAMOO\CDN\Exception\FileStateException;

final class SyncFiles
{
    public function sync(): void
    {

        // Example of setting options directly. Adjust, according to actual CDN and domain configuration
        $wpVersion = get_bloginfo('version');
        $cdnUrl = "https://cm-s3.camoo.hosting/492220c341f7489996eb01871a2e8aca:wordpress/{$wpVersion}";
        update_option('ossdl_off_cdn_url', $cdnUrl);
        update_option('ossdl_off_blog_url', get_site_url());

        // Read ossdl_off_include_dirs, default to wp-content, wp-includes if empty
        $includeDirs = get_option('ossdl_off_include_dirs', 'wp-content,wp-includes');
        $directories = explode(',', $includeDirs);

        $fileList = new FileList();
        // Iterate over directories and process files
        foreach ($directories as $dir) {
            $dirPath = ABSPATH . trim($dir);
            if (!file_exists($dirPath)) {
                continue; // Skip non-existing directories
            }

            $fileListGenerator = $fileList->get($dirPath);
            $this->processFiles($fileListGenerator);
        }
        $this->updateWpSuperCache();
    }

    private function updateSnapshot($current_files): void
    {
        update_option(WP_CAMOO_CDN_SNAPSHOT, $current_files);
    }

    private function updateWpSuperCache(): void
    {

        WP_Filesystem(); // Initialize the WordPress filesystem, will prompt for credentials if necessary

        global $wp_filesystem;

        $cache_config_path = WP_CONTENT_DIR . '/wp-cache-config.php';
        if ($wp_filesystem->exists($cache_config_path)) {
            $config_content = $wp_filesystem->get_contents($cache_config_path);

            // Patterns and replacements for configuration changes
            $patterns = [
                "/^\$ossdlcdn\s*=\s*0;/m",
                "/^\$cache_enabled\s*=\s*false;/m",
                "/^\$super_cache_enabled\s*=\s*false;/m",
                "/^\$wp_cache_not_logged_in\s*=\s*0;/m",
                "/^\$wp_supercache_304\s*=\s*false;/m",
                "/^\$wp_cache_clear_on_post_edit\s*=\s*false;/m",
                "/^\$wp_cache_front_page_checks\s*=\s*false;/m",
                "/^\$wp_cache_mobile_enabled\s*=\s*false;/m",
                "/^\$cache_compression\s*=\s*false;/m",
            ];
            $replacements = [
                '$ossdlcdn = 1;',
                '$cache_enabled = true;',
                '$super_cache_enabled = true;',
                '$wp_cache_not_logged_in = 2;',
                '$wp_supercache_304 = 1;',
                '$wp_cache_clear_on_post_edit = 1;',
                '$wp_cache_front_page_checks = 1;',
                '$wp_cache_mobile_enabled = 1;',
                '$cache_compression = 1;',
            ];

            // Perform replacements
            $new_config_content = preg_replace($patterns, $replacements, $config_content);

            // Write the new configuration back to the file
            $wp_filesystem->put_contents($cache_config_path, $new_config_content);
        }
    }

    private function processFiles($fileListGenerator): void
    {
        $syncSnapshots = new SyncSnapshots();
        $currentFiles = [];
        foreach ($syncSnapshots->compare($fileListGenerator) as $fileStateDTO) {
            match ($fileStateDTO->state) {
                'new', 'modified' => $this->proceedNewOrModifiedFile($fileStateDTO->path, $currentFiles),
                'deleted' => $this->proceedDeleteFile($fileStateDTO->path),
                default => throw new FileStateException("Unknown file state: {$fileStateDTO->state}"),
            };
        }
        $this->updateSnapshot($currentFiles);
    }

    private function proceedNewOrModifiedFile(string $file, array &$currentFiles): void
    {
        // Implement logic to handle new or modified files
        // This might involve uploading the file to the CDN and updating $currentFiles with the file's state
    }

    private function proceedDeleteFile(string $file): void
    {
        // Implement logic to handle deleted files
        // This might involve removing the file from the CDN
    }
}
