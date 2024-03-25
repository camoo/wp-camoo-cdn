<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

final class Integration
{
    /** Initializes the plugin's hooks and schedules. */
    public static function initialize(): void
    {

        // Check if WP Super Cache is active
        if (!is_plugin_active('wp-super-cache/wp-cache.php')) {
            add_action('admin_notices', [__CLASS__, 'wp_super_cache_missing_notice']);

            return;
        }

        add_action('plugins_loaded', [__CLASS__, 'init_actions']);
        register_activation_hook(WP_CAMOO_CDN_DIR . 'camoo-cdn.php', [Install::class, 'install']);
        register_activation_hook(WP_CAMOO_CDN_DIR . 'camoo-cdn.php', [__CLASS__, 'onActivation']);
        register_deactivation_hook(WP_CAMOO_CDN_DIR . 'camoo-cdn.php', [__CLASS__, 'onDeactivation']);
    }

    public static function onActivation(): void
    {
        self::schedule_sync_soon();
    }

    public static function wp_super_cache_missing_notice(): void
    {
        ?>
        <div class="notice notice-warning">
            <p><?php _e(
                'WP Super Cache is not active. Please activate WP Super Cache for the CAMOO CDN plugin to work correctly.',
                'camoo-cdn'
            ); ?></p>
        </div>
        <?php
    }

    /** Sets up actions and filters, including custom cron schedules. */
    public static function init_actions(): void
    {
        add_filter('cron_schedules', [__CLASS__, 'addCustomCronSchedule']);
        self::schedule_regular_sync();
        add_action('wp_camoo_cdn_cron', [SyncFiles::class, 'sync']); // Regular sync
        add_action('wp_camoo_cdn_cron_soon', [SyncFiles::class, 'sync']); // Immediate sync
    }

    /**
     * Adds custom intervals to the cron schedules.
     *
     * @param mixed $schedules Existing cron schedules.
     *
     * @return array Modified cron schedules.
     */
    public static function addCustomCronSchedule($schedules): array
    {
        $schedules['camoo_cdn_cron_every_four_days'] = [
            'interval' => 4 * DAY_IN_SECONDS,
            'display' => __('Once Every 4 Days', 'camoo-cdn'),
        ];

        return $schedules;
    }

    /** Schedules an immediate sync task. */
    public static function schedule_sync_soon(): void
    {
        if (wp_next_scheduled('wp_camoo_cdn_cron_soon')) {
            wp_clear_scheduled_hook('wp_camoo_cdn_cron_soon');
        }
        wp_schedule_single_event(time() + MINUTE_IN_SECONDS, 'wp_camoo_cdn_cron_soon');
    }

    /** Clears scheduled tasks on plugin deactivation. */
    public static function onDeactivation(): void
    {
        $timestamp = wp_next_scheduled('wp_camoo_cdn_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wp_camoo_cdn_cron');
        }
        wp_clear_scheduled_hook('wp_camoo_cdn_cron_soon');

        delete_option('ossdl_off_cdn_url');
        delete_option('ossdl_off_blog_url');

        $configFile = WP_CONTENT_DIR . '/wp-cache-config.php';
        if (file_exists($configFile)) {
            wp_cache_replace_line('^ *\$ossdlcdn', '$ossdlcdn = 0;', $configFile);
        }
    }

    /** Schedules the regular sync if it's not already scheduled. */
    private static function schedule_regular_sync(): void
    {
        if (!wp_next_scheduled('wp_camoo_cdn_cron')) {
            wp_schedule_event(time(), 'camoo_cdn_cron_every_four_days', 'wp_camoo_cdn_cron');
        }
    }
}
