<?php
/**
 * HeritagePress - WordPress Genealogy Plugin
 *
 * @package     HeritagePress
 * @author      HeritagePress Team
 * @copyright   2023 HeritagePress
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: HeritagePress
 * Plugin URI:  https://heritagepress.com
 * Description: A comprehensive genealogy and family history research plugin for WordPress
 * Version:     1.0.0
 * Author:      HeritagePress Team
 * Author URI:  https://heritagepress.com
 * Text Domain: heritagepress
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('HP_PLUGIN_VERSION', '1.0.0');

// Plugin paths
define('HP_PLUGIN_FILE', __FILE__);
define('HP_PLUGIN_PATH', dirname(HP_PLUGIN_FILE) . '/');
define('HP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load autoloader
require_once HP_PLUGIN_PATH . 'includes/class-heritagepress-autoloader.php';
HeritagePress_Autoloader::register();

/**
 * Activate the plugin
 */
function heritagepress_activate()
{
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Initialize database tables
    $db_manager = new HeritagePress\Database\Manager();
    $db_manager->init_tables();

    // Add capabilities
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_heritagepress');
    }

    // Clear rewrite rules
    flush_rewrite_rules();
}

/**
 * Deactivate the plugin
 */
function heritagepress_deactivate()
{
    // Remove capabilities
    $role = get_role('administrator');
    if ($role) {
        $role->remove_cap('manage_heritagepress');
    }

    // Clear rewrite rules
    flush_rewrite_rules();
}

/**
 * Initialize plugin
 */
function heritagepress_init()
{
    // Initialize admin interface
    if (is_admin()) {
        $admin = new HeritagePress\Admin\Admin();
        $admin->init();
    }

    // Load text domain
    load_plugin_textdomain('heritagepress', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'heritagepress_activate');
register_deactivation_hook(__FILE__, 'heritagepress_deactivate');

// Initialize plugin
add_action('init', 'heritagepress_init');