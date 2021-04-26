<?php
declare(strict_types=1);
/**
 * Class WPCamooCDN
 *
 * Plugin Name: WordPress CAMOO CDN
 * Plugin URI:  https://github.com/camoo/wp-camoo-cdn
 * Description: Camoo.Hosting CDN Integration for WordPress
 * Version:     1.0
 * Author:      CAMOO SARL
 * Author URI:  https://www.camoo.hosting/
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: wp-camoo-cdn
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.3
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if (! defined('ABSPATH')) {
    die('Invalid request.');
}

require_once plugin_dir_path(__FILE__) . 'src/Bootstrap.php';
(new \WP_CAMOO\CDN\Bootstrap)->initialze();
