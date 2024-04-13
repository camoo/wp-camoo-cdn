<?php
declare(strict_types=1);

namespace WP_CAMOO\CDN\Cache;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

final class SettingRenderer
{
    // Settings Page
    public static function display(): void
    {
        ?>
        <div class="wrap">
            <h2><?php echo __('CAMOO CDN Settings', 'camoo-cdn')?></h2>
            <form action="options.php" method="post">
                <?php
                settings_fields('camoo_cdn_options');
        do_settings_sections('camoo_cdn');
        submit_button();
        ?>
            </form>
        </div>
        <?php
    }
}
