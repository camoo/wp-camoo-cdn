<?php
declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

use WP_CAMOO\CDN\Bootstrap;
use  WP_CAMOO\CDN\Gateways\Option;

/**
 * Class Install
 * @author CamooSarl
 */
class Install
{
    private function __construct()
    {
    }

    /**
     * Creating plugin tables
     *
     * @param $network_wide
     */
    public static function install()
    {
        Option::add('wp_camoo_cdn_db_version', WP_CAMOO_CDN_VERSION);
        Option::delete('wp_notification_new_wp_version');

        $url = Option::get();

        if (empty($url)) {
            Option::add('wp_camoo_cdn_url', 'https://cdn.camoo.hosting');
        }

        if (is_admin()) {
            self::upgrade();
        }
    }


    /**
     * Upgrade plugin requirements if needed
     */
    public static function upgrade()
    {
        $installer_wpcamoosms_ver = Option::get('wp_camoo_cdn_db_version');

        if ($installer_wpcamoosms_ver < WP_CAMOO_CDN_VERSION) {
            Option::update('wp_camoo_cdn_db_version', WP_CAMOO_CDN_VERSION);
        }
    }
}
