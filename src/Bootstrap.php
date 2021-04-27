<?php
declare(strict_types=1);

namespace WP_CAMOO\CDN;

if (! defined('ABSPATH')) {
    exit;
}
use WP_CAMOO\CDN\Services\Integration;

/**
 * Class Bootstrap
 * @author CamooSarl
 */
final class Bootstrap
{
    const PLUGIN_MAIN_FILE = 'camoo-cdn/camoo-cdn.php';
    public function initialze() : void
    {
        require_once dirname(plugin_dir_path(__FILE__)) . '/config/defines.php';
        require_once dirname(plugin_dir_path(__FILE__)) . '/vendor/autoload.php';
        Integration::initialze();
        add_filter('all_plugins', array($this, 'modify_plugin_description' ));
    }

    public function modify_plugin_description($all_plugins)
    {
        if (isset($all_plugins[self::PLUGIN_MAIN_FILE])) {
            $all_plugins[static::PLUGIN_MAIN_FILE]['Description'] = sprintf(__('Camoo.Hosting Automatic Integration with CDN for WordPress. Check our <a target="_blank" href="%s">Managed WordPress packages</a> out for more.', 'camoo-cdn'), WP_CAMOO_CDN_SITE  .'/wordpress-hosting');
        }
        return $all_plugins;
    }
}
