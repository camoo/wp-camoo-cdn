<?php
declare(strict_types=1);

namespace WP_CAMOO\CDN;

use WP_CAMOO\CDN\Services\Integration;

final class Bootstrap
{
    const PLUGIN_MAIN_FILE = 'camoo-cdn/camoo-cdn.php';

    public function initialize(): void
    {
        $this->loadDependencies();
        Integration::initialize();
        $this->addHooks();
    }

    private function loadDependencies(): void
    {
        require_once dirname(plugin_dir_path(__FILE__)) . '/config/defines.php';
        require_once dirname(plugin_dir_path(__FILE__)) . '/vendor/autoload.php';
    }

    private function addHooks(): void
    {
        add_filter('all_plugins', [$this, 'modifyPluginDescription']);
        add_action('wpsc_after_delete_cache_admin_bar', [$this, 'scheduleImmediateSync'], 10, 2);
    }



    public function scheduleImmediateSync($req_path, $referer): void
    {
        Integration::schedule_sync_soon();
    }

    public function modifyPluginDescription($all_plugins): array
    {
        if (isset($all_plugins[self::PLUGIN_MAIN_FILE])) {
            $all_plugins[self::PLUGIN_MAIN_FILE]['Description'] = sprintf(
                __('Camoo.Hosting Automatic Integration with CDN for WordPress. Check our <a target="_blank" href="%s">Managed WordPress packages</a> out for more.', 'camoo-cdn'),
                esc_url(WP_CAMOO_CDN_SITE . '/wordpress-hosting')
            );
        }
        return $all_plugins;
    }
}
