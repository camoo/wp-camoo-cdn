<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Check get_plugin_data function exist
 */
if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Set Plugin path and url defines.
define('WP_CAMOO_CDN_URL', plugin_dir_url(dirname(__FILE__)));
define('WP_CAMOO_CDN_DIR', plugin_dir_path(dirname(__FILE__)));

// Get plugin Data.
$plugin_data = get_plugin_data(WP_CAMOO_CDN_DIR . 'camoo-cdn.php');

// Set another useful Plugin definition.
define('WP_CAMOO_CDN_VERSION', $plugin_data['Version']);

const WP_CAMOO_CDN_SITE = 'https://www.camoo.hosting';
const WP_CAMOO_CDN_SNAPSHOT = 'camoo_cdn_sync_snapshot';
const WP_CAMOO_CDN_STATIC_FILES_PATTERN = '/\.(jpg|jpeg|png|gif|css|js|pdf|txt|woff|woff2|svg)$/i';
