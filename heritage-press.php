<?php
/**
 * Plugin Name: Heritage Press
 * Plugin URI: http://heritagepress.org
 * Description: A comprehensive genealogy management system for WordPress.
 * Version: 1.0.0
 * Author: Heritage Press
 * Author URI: http://heritagepress.org
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: heritage-press
 * Domain Path: /languages
 *
 * @package HeritagePress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version and directory constants
if (!defined('HERITAGE_PRESS_VERSION')) {
    define('HERITAGE_PRESS_VERSION', '1.0.0');
}

// Load WordPress compatibility functions for IDE support
if (!function_exists('plugin_dir_path')) {
    require_once dirname(__FILE__) . '/includes/wordpress-compatibility.php';
}

if (!defined('HERITAGE_PRESS_PLUGIN_DIR')) {
    define('HERITAGE_PRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Register autoloader
require_once HERITAGE_PRESS_PLUGIN_DIR . 'includes/Autoloader.php';
HeritagePress\Core\Autoloader::register();

// Register activation and deactivation hooks
if (function_exists('register_activation_hook') && function_exists('register_deactivation_hook')) {
    register_activation_hook(__FILE__, ['HeritagePress\Core\Activator', 'activate']);
    register_deactivation_hook(__FILE__, ['HeritagePress\Core\Deactivator', 'deactivate']);
}

// Initialize the plugin
if (class_exists('HeritagePress\Core\Plugin')) {
    $heritage_press = HeritagePress\Core\Plugin::get_instance();
    $heritage_press->run();
}
