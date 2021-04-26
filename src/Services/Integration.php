<?php
declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

use  WP_CAMOO\CDN\Gateways\Option;

use DOMDocument;

/**
 * Class Integration
 * @author CamooSarl
 */
final class Integration
{
    private function __construct()
    {
        add_action('plugins_loaded', [Integration::class, 'init_actions']);
        register_activation_hook(WP_CAMOO_CDN_DIR . 'wp-camoo-cdn.php', [Install::class, 'install']);
        register_deactivation_hook(WP_CAMOO_CDN_DIR . 'wp-camoo-cdn.php', [$this, 'cdn_status_plugin_deactivate']);
    }

    public static function initialze(): void
    {
        new static;
    }

    public static function init_actions() : void
    {
        // Performance
        add_filter('style_loader_src', [__CLASS__, 'remove_cssjs_ver'], 10, 2);
        add_filter('script_loader_src', [__CLASS__, 'remove_cssjs_ver'], 10, 2);
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'wlwmanifest_link');
        add_action('wp_deregister_script', [__CLASS__, 'stop_heartbeat'], 1);
        remove_action('wp_head', 'rsd_link');
        add_filter('xmlrpc_enabled', '__return_false');

        // CDN
        add_filter('the_content', [__CLASS__,'cdn_content_urls']);
        // head
        add_action('wp_head', [__CLASS__,'start_wp_head_buffer'], 0);
        add_action('wp_head', [__CLASS__, 'cdn_head_urls'], PHP_INT_MAX);
        // footer
        add_action('wp_footer', [__CLASS__,'start_wp_head_buffer'], 0);
        add_action('wp_footer', [__CLASS__, 'cdn_footer_urls'], PHP_INT_MAX);
    }

    public static function stop_heartbeat() : void
    {
        wp_deregister_script('heartbeat');
    }

    public static function remove_cssjs_ver(string $src) : string
    {
        if (strpos($src, '?ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    private static function getDomain() : string
    {
        $urlparts = parse_url(home_url());
        return $urlparts['host'];
    }

    private static function getCDNUrl() : string
    {
        return Option::get();
    }

    public static function start_wp_head_buffer()  : void
    {
        ob_start();
    }

    public static function cdn_head_urls() : void
    {
        $content = ob_get_clean();

        $doc = new DOMDocument();
        $cdnUrl = self::getCDNUrl();
        $doc->loadHTML($content);
        $domcss = $doc->getElementsByTagName('link');
        $domJs = $doc->getElementsByTagName('script');
        $domain = self::getDomain();
        // CSS
        if ($domcss->length > 0) {
            foreach ($domcss as $links) {
                if (strtolower($links->getAttribute('rel')) === "stylesheet") {
                    $cssLink = $links->getAttribute('href');
                    $urlParsed = parse_url($cssLink);
                    $host = $urlParsed['host'];
                    if (in_array($host, [$domain, 'www.' . $domain])) {
                        $content = preg_replace_callback(sprintf("#%s#", $cssLink), function ($match) use ($host, $cdnUrl) {
                            return str_replace(['http://'. $host, 'https://'. $host], $cdnUrl, $match[0]);
                        }, $content);
                    }
                }
            }
        }
        // JS
        if ($domJs->length > 0) {
            foreach ($domJs as $links) {
                $jsLink = $links->getAttribute('src');
                $urlParsed = parse_url($jsLink);
                $host = $urlParsed['host'];
                if (in_array($host, [$domain, 'www.' . $domain])) {
                    $content = preg_replace_callback(sprintf("#%s#", $jsLink), function ($match) use ($host, $cdnUrl) {
                        return str_replace(['http://'. $host, 'https://'. $host], $cdnUrl, $match[0]);
                    }, $content);
                }
            }
        }

        print $content;
    }

    public static function cdn_footer_urls() : void
    {
        $content = ob_get_clean();

        $doc = new DOMDocument();
        $doc->loadHTML($content);
        $cdnUrl = self::getCDNUrl();
        $domcss = $doc->getElementsByTagName('link');
        $domJs = $doc->getElementsByTagName('script');
        $domain = self::getDomain();
        // CSS
        if ($domcss->length > 0) {
            foreach ($domcss as $links) {
                if (strtolower($links->getAttribute('rel')) === "stylesheet") {
                    $cssLink = $links->getAttribute('href');
                    $urlParsed = parse_url($cssLink);
                    $host = $urlParsed['host'];
                    if (in_array($host, [$domain, 'www.' . $domain])) {
                        $content = preg_replace_callback(sprintf("#%s#", $cssLink), function ($match) use ($host, $cdnUrl) {
                            return str_replace(['http://'. $host, 'https://'. $host], $cdnUrl, $match[0]);
                        }, $content);
                    }
                }
            }
        }
        // JS
        if ($domJs->length > 0) {
            foreach ($domJs as $links) {
                $jsLink = $links->getAttribute('src');
                $urlParsed = parse_url($jsLink);
                $host = $urlParsed['host'];
                if (in_array($host, [$domain, 'www.' . $domain])) {
                    $content = preg_replace_callback(sprintf("#%s#", $jsLink), function ($match) use ($host, $cdnUrl) {
                        return str_replace(['http://'. $host, 'https://'. $host], $cdnUrl, $match[0]);
                    }, $content);
                }
            }
        }

        print $content;
    }

    public static function cdn_content_urls(string $content) : string
    {
        $domain = self::getDomain();
        $cdnUrl = self::getCDNUrl();
        $content = str_replace(['http://' . $domain.'/wp-content/uploads', 'http://www.' . $domain .'/wp-content/uploads'], $cdnUrl."/wp-content/uploads", $content);
        $content = str_replace(['https://' . $domain.'/wp-content/uploads', 'https://www.' . $domain .'/wp-content/uploads'], $cdnUrl."/wp-content/uploads", $content);
        return $content;
    }

    public function cdn_status_plugin_deactivate()
    {
        flush_rewrite_rules();
    }
}
