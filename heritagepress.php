<?php
/**
 * Plugin Name: HeritagePress
 * Plugin URI: https://github.com/TheRev/HeritagePress
 * Description: A comprehensive genealogy management plugin for WordPress
 * Version: 1.0.0
 * Author: Joseph Cox
 * Author URI: https://github.com/TheRev
 * Text Domain: heritagepress
 * License: GPL v2 or later
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HP_PLUGIN_VERSION', '1.0.0');
define('HP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Use Composer's autoloader
require_once HP_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize the plugin
add_action('plugins_loaded', 'heritagepress_init');

/**
 * Initialize the plugin
 */
function heritagepress_init() {
    // Initialize admin interface
    if (is_admin()) {
        $admin = new HeritagePress\Admin\Admin();
        $admin->init();
    }

    // Load text domain for translations
    load_plugin_textdomain('heritagepress', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Activation Hook
register_activation_hook(__FILE__, 'heritagepress_activate');

// Deactivation Hook
register_deactivation_hook(__FILE__, 'heritagepress_deactivate');

/**
 * Plugin activation
 */
function heritagepress_activate() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('HeritagePress requires PHP 7.4 or higher.');
    }

    // Check WordPress version
    if (version_compare($GLOBALS['wp_version'], '5.8', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('HeritagePress requires WordPress 5.8 or higher.');
    }

    // Initialize database tables
    $db_manager = new HeritagePress\Database\Manager();
    $db_manager->init_tables();

    // Set version in options
    update_option('heritagepress_version', HP_PLUGIN_VERSION);

    // Create necessary folders
    wp_mkdir_p(wp_upload_dir()['basedir'] . '/heritagepress');

    // Add capabilities to administrators
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_heritagepress');
    }
}

/**
 * Plugin deactivation
 */
function heritagepress_deactivate() {
    // Remove capabilities from administrators
    $role = get_role('administrator');
    if ($role) {
        $role->remove_cap('manage_heritagepress');
    }
}