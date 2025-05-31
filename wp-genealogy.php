<?php
/**
 * Plugin Name: WP Genealogy
 * Plugin URI: http://heritagepress.org
 * Description: A WordPress plugin for managing genealogical data.
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
if (!defined('HERITAGE_PRESS_PLUGIN_DIR')) {
    define('HERITAGE_PRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Register autoloader
require_once HERITAGE_PRESS_PLUGIN_DIR . 'includes/class-autoloader.php';
HeritagePress\Core\Autoloader::register();

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['HeritagePress\Core\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['HeritagePress\Core\Deactivator', 'deactivate']);

// Add admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'WP Genealogy',
        'WP Genealogy',
        'manage_options',
        'heritage-press',
        'heritage_press_admin_page'
    );
});

/**
 * Display the admin page
 */
function heritage_press_admin_page() {
    global $wpdb;
    $prefix = $wpdb->prefix . 'genealogy_';
    $tables = [
        'individuals',
        'families',
        'events',
        'places',
        'sources',
        'repositories'
    ];
    ?>
    <div class="wrap">
        <h1>WP Genealogy</h1>
        <h2>Plugin Status</h2>
        <table class="widefat" style="max-width: 500px; margin-top: 20px;">
            <tbody>
                <tr>
                    <td><strong>Plugin Version</strong></td>
                    <td><?php echo esc_html(HERITAGE_PRESS_VERSION); ?></td>
                </tr>
                <tr>
                    <td><strong>Activation Status</strong></td>
                    <td><?php echo get_option('heritage_press_version') ? '✅ Active' : '❌ Not activated'; ?></td>
                </tr>
                <tr>
                    <td><strong>Database Tables</strong></td>
                    <td>
                        <?php
                        foreach ($tables as $table) {
                            $table_name = $prefix . $table;
                            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                            echo esc_html($table) . ': ' . ($exists ? '✅' : '❌') . '<br>';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}

// Initialize the plugin
if (class_exists('HeritagePress\Plugin')) {    $heritage_press = new HeritagePress\Plugin();
    $heritage_press->run();
}
