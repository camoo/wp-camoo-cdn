<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

use WP_CAMOO\CDN\Dto\FileState;
use WP_CAMOO\CDN\Exception\FileStateException;
use WP_CAMOO\CDN\Gateways\Option;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

final class SyncFiles
{
    public static function sync(): void
    {
        $domain = parse_url(home_url(), PHP_URL_HOST);

        if (!self::canCDN()) {
            $packages_url = esc_url(WP_CAMOO_CDN_SITE . '/wordpress-hosting');
            $domain = parse_url(home_url(), PHP_URL_HOST);
            $link_text = __('Managed WordPress packages', 'camoo-cdn');
            $message_format = __('CDN is not available for your domain: %s. Check our %s out for more.', 'camoo-cdn');
            $link_html = sprintf('<a href="%s" target="_blank">%s</a>', esc_url($packages_url), esc_html($link_text));
            $fullMessage = sprintf($message_format, esc_html($domain), $link_html);

            self::logError($fullMessage);

            return;
        }

        $cdnUrl = WP_CAMOO_CDN_URL . '/' . $domain;
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
            self::processFiles($fileListGenerator);
        }
        self::updateWpSuperCache();
    }

    private static function updateWpSuperCache(): void
    {
        $oss_configured = get_option('wp_camoo_cdn_oss');
        if ($oss_configured) {
            return;
        }
        $configFile = WP_CONTENT_DIR . '/wp-cache-config.php';
        if (!file_exists($configFile)) {
            return;
        }

        // An array of configurations to be replaced in wp-cache-config.php
        $configurations = [
            'ossdlcdn' => '$ossdlcdn = 1;',
            'cache_enabled' => '$cache_enabled = true;',
            'super_cache_enabled' => '$super_cache_enabled = true;',
            'wp_cache_not_logged_in' => '$wp_cache_not_logged_in = 2;',
            'wp_supercache_304' => '$wp_supercache_304 = 1;',
            'wp_cache_clear_on_post_edit' => '$wp_cache_clear_on_post_edit = 1;',
            'wp_cache_front_page_checks' => '$wp_cache_front_page_checks = 1;',
            'wp_cache_mobile_enabled' => '$wp_cache_mobile_enabled = 1;',
            'cache_compression' => '$cache_compression = 1;',
        ];

        foreach ($configurations as $key => $replacement) {
            wp_cache_replace_line('^ *\$' . $key, $replacement, $configFile);
        }
        Option::add('wp_camoo_cdn_oss', 1);
    }

    private static function canCDN(): bool
    {
        $api_url = WP_CAMOO_CDN_SITE . '/cpanel/managed-wordpress/can-cdn.json?dn=' .
            urlencode(parse_url(home_url(), PHP_URL_HOST));
        $response = wp_remote_get($api_url);

        if (is_wp_error($response)) {
            self::logError('Error checking CDN availability: ' . $response->get_error_message());

            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return !empty($data) && isset($data['status']) && $data['status'];
    }

    private static function logError($message): void
    {
        set_transient('wp_camoo_cdn_sync_message', $message, 60 * 10);
    }

    private static function updateSnapshot($current_files): void
    {
        update_option(WP_CAMOO_CDN_SNAPSHOT, $current_files);
    }

    private static function processFiles($fileListGenerator): void
    {
        $syncSnapshots = new SyncSnapshots();
        $currentFiles = [];
        foreach ($syncSnapshots->compare($fileListGenerator) as $fileStateDTO) {
            match ($fileStateDTO->state) {
                SyncSnapshots::STATE_NEW,SyncSnapshots::STATE_MODIFIED => self::proceedNewOrModifiedFile(
                    $fileStateDTO,
                    $currentFiles
                ),
                SyncSnapshots::STATE_DELETED => self::proceedDeleteFile($fileStateDTO->path),
                default => throw new FileStateException('Unknown file state: ' . $fileStateDTO->state),
            };
        }
        self::updateSnapshot($currentFiles);
    }

    private static function proceedNewOrModifiedFile(FileState $fileState, array &$currentFiles): void
    {
        $api_url = WP_CAMOO_CDN_SITE . '/cpanel/managed-wordpress/import-cdn-file.json';

        $file = $fileState->path;

        if (!file_exists($file)) {
            return;
        }

        $file_contents = file_get_contents($file);

        // Get the full directory path excluding the filename
        $directory_path = dirname($file);

        // Get the relative path from WordPress root directory to the directory containing the file
        $relative_directory_path = str_replace(ABSPATH, '', $directory_path);

        $response = wp_remote_post($api_url, [
            'body' => [
                'file' => base64_encode($file_contents),
                'file_path' => $relative_directory_path,
                'filename' => basename($file),
                'domain' => parse_url(home_url(), PHP_URL_HOST),
            ],
        ]);

        // Handle error, for instance, logging it
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            set_transient(
                'wp_camoo_cdn_sync_message',
                'Error syncing file: ' . $error_message,
                60 * 10
            );

            return;
        }

        // Add the file to the currentFiles list if successful
        $currentFiles[$file] = $fileState->mtime;
    }

    private static function proceedDeleteFile(string $file): void
    {
        $current_domain = parse_url(home_url(), PHP_URL_HOST);

        // Get the full directory path excluding the filename
        $directory_path = dirname($file);

        // Get the relative path from WordPress root directory to the directory containing the file
        $relative_directory_path = str_replace(ABSPATH, '', $directory_path);

        $filename = basename($file);

        $api_url = WP_CAMOO_CDN_SITE . '/cpanel/managed-wordpress/delete-cdn-file.json?dn=' .
            urlencode($current_domain) . '&filename=' . urlencode($filename) .
            '&file_path=' . urlencode($relative_directory_path);

        // Perform the HTTP request to the API
        $response = wp_remote_get($api_url);

        // Check if the request returned an error.
        if (is_wp_error($response)) {
            // Log the error or handle it as needed.
            set_transient(
                'wp_camoo_cdn_sync_message',
                'Error deleting CDN file: ' . $response->get_error_message(),
                60 * 10
            );

            return;
        }

        // Decode the response body.
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true); // Assuming the response is in JSON format.

        // Validate the response data.
        if (!is_array($data) || !isset($data['status'])) {
            // Log an unexpected response format or handle it as needed.

            set_transient(
                'wp_camoo_cdn_sync_message',
                'Unexpected response format when deleting cdn file: ' . $file,
                60 * 10
            );
        }
    }
}
