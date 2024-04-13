<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

use WP_CAMOO\CDN\Gateways\Option;
use WP_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class QueryCaching
{
    /** @var array|null Cache settings loaded once per request. */
    private static ?array $options = null;

    /**
     * Handles reading and potentially caching query results.
     *
     * @param array    $posts The posts retrieved by the query.
     * @param WP_Query $query The WP_Query instance.
     *
     * @return array The possibly modified posts array.
     */
    public static function read(array $posts, WP_Query $query): array
    {
        // Only modify main query and non-admin pages.
        if (!is_admin() && $query->is_main_query()) {
            $currentUrl = self::getCurrentUrl();

            if (!self::shouldCache() || self::isExcluded($currentUrl)) {
                return $posts;
            }

            $transientName = 'camoo_cdn_' . md5($currentUrl);
            $cachedPosts = get_transient($transientName);

            if ($cachedPosts !== false) {
                return $cachedPosts;
            }

            $cacheDuration = self::getCacheDuration();
            set_transient($transientName, $posts, HOUR_IN_SECONDS * $cacheDuration);
        }

        return $posts;
    }

    /** Clears all cached content related to this feature. */
    public static function clear(): void
    {
        global $wpdb;
        $wpdb->query("
           DELETE FROM {$wpdb->options}
           WHERE option_name LIKE '\_transient\_camoo\_cdn\_%' OR option_name LIKE '\_transient\_timeout\_camoo\_cdn\_%'
        ");
    }

    private static function shouldCache(): bool
    {
        $options = self::getOptions();

        return isset($options['enable_caching']) && $options['enable_caching'];
    }

    /**
     * Ensures that settings are loaded only once per request.
     *
     * @return array The settings array.
     */
    private static function getOptions(): array
    {
        if (self::$options === null) {
            self::$options = Option::get('camoo_cdn_cache_settings') ?? [];
        }

        return self::$options;
    }

    /**
     * Determines if the current URL is excluded from caching.
     *
     * @param string $url The current URL.
     *
     * @return bool True if the URL is excluded, false otherwise.
     */
    private static function isExcluded(string $url): bool
    {
        $excludedPages = self::getExcludedPages();

        return in_array($url, $excludedPages, true);
    }

    /**
     * Retrieves excluded pages from settings.
     *
     * @return array List of excluded page URLs.
     */
    private static function getExcludedPages(): array
    {
        $options = self::getOptions();
        $excludedPages = $options['excluded_pages'] ?? '';

        return array_map('trim', explode(',', $excludedPages));
    }

    /**
     * Fetches the cache duration from settings, defaulting to 1 hour if not set.
     *
     * @return int Cache duration in hours.
     */
    private static function getCacheDuration(): int
    {
        $options = self::getOptions();

        return max((int)($options['cache_duration'] ?? 1), 1);
    }

    /**
     * Constructs the current URL.
     *
     * @return string The full URL of the current request.
     */
    private static function getCurrentUrl(): string
    {
        return home_url(add_query_arg(null, null));
    }
}
