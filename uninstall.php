<?php

declare(strict_types=1);

/**
 * Uninstalling CAMOO-CDN, deletes tables, and options.
 *
 * @version 2.0
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('wp_camoo_cdn_oss');
delete_option('ossdl_off_cdn_url');
delete_option('ossdl_off_blog_url');
delete_option('wp_camoo_cdn_db_version');

$configFile = WP_CONTENT_DIR . '/wp-cache-config.php';
if (file_exists($configFile)) {
    wp_cache_replace_line('^ *\$ossdlcdn', '$ossdlcdn = 0;', $configFile);
}
