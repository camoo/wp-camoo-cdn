<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

use WP_CAMOO\CDN\Gateways\Option;

/**
 * Class Install
 *
 * @author CamooSarl
 */
final class Install
{
    /** Creating plugin tables */
    public static function install(): void
    {
        Option::add('wp_camoo_cdn_db_version', WP_CAMOO_CDN_VERSION);

        if (is_admin()) {
            self::upgrade();
        }
    }

    /** Upgrade plugin requirements if needed */
    public static function upgrade(): void
    {
        $version = Option::get('wp_camoo_cdn_db_version');

        if ($version < WP_CAMOO_CDN_VERSION) {
            Option::update('wp_camoo_cdn_db_version', WP_CAMOO_CDN_VERSION);
        }
    }
}
