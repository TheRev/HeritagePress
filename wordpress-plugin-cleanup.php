<?php
/**
 * WordPress Plugin Cleanup Script
 * Run this script to clean up orphaned Heritage Press plugin entries
 * 
 * Instructions:
 * 1. Upload this file to your WordPress root directory
 * 2. Access it via: http://yoursite.com/wordpress-plugin-cleanup.php
 * 3. Run the cleanup
 * 4. Delete this file after use
 */

// Prevent direct access without WordPress
if (!defined('ABSPATH')) {
    // Try to find WordPress config
    $wp_config_paths = [
        __DIR__ . '/wp-config.php',
        dirname(__DIR__) . '/wp-config.php',
        __DIR__ . '/wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_config_paths as $path) {
        if (file_exists($path)) {
            if (strpos($path, 'wp-load.php') !== false) {
                require_once $path;
            } else {
                require_once $path;
                require_once __DIR__ . '/wp-settings.php';
            }
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('Error: Could not load WordPress. Please place this file in your WordPress root directory.');
    }
}

// Security check
if (!current_user_can('activate_plugins')) {
    die('Error: You do not have permission to manage plugins.');
}

echo '<h1>Heritage Press Plugin Cleanup</h1>';

// Function to clean up plugin data
function cleanup_heritage_press_plugin() {
    global $wpdb;
    
    echo '<h2>Starting Cleanup Process...</h2>';
    
    // 1. Remove from active plugins list
    $active_plugins = get_option('active_plugins', []);
    $heritage_plugins = [];
    
    foreach ($active_plugins as $key => $plugin) {
        if (strpos($plugin, 'heritage-press') !== false) {
            $heritage_plugins[] = $plugin;
            unset($active_plugins[$key]);
        }
    }
    
    if (!empty($heritage_plugins)) {
        echo '<p>✓ Removed from active plugins: ' . implode(', ', $heritage_plugins) . '</p>';
        update_option('active_plugins', array_values($active_plugins));
    } else {
        echo '<p>• No active Heritage Press plugins found</p>';
    }
    
    // 2. Check for plugin data in options table
    $heritage_options = $wpdb->get_results(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%heritage%' OR option_name LIKE '%Heritage%'"
    );
    
    if (!empty($heritage_options)) {
        echo '<p>Found Heritage Press options:</p><ul>';
        foreach ($heritage_options as $option) {
            echo '<li>' . esc_html($option->option_name) . '</li>';
        }
        echo '</ul>';
        
        // Option to delete these
        echo '<p><strong>Warning:</strong> This will delete all Heritage Press plugin data!</p>';
        echo '<form method="post">';
        echo '<input type="hidden" name="action" value="delete_options">';
        echo '<input type="submit" value="Delete Heritage Press Options" onclick="return confirm(\'Are you sure? This will delete all plugin data!\');">';
        echo '</form>';
    } else {
        echo '<p>✓ No Heritage Press options found in database</p>';
    }
    
    // 3. Check for plugin tables
    $tables = $wpdb->get_results(
        "SHOW TABLES LIKE '{$wpdb->prefix}heritage_%'"
    );
    
    if (!empty($tables)) {
        echo '<p>Found Heritage Press tables:</p><ul>';
        foreach ($tables as $table) {
            $table_name = array_values((array)$table)[0];
            echo '<li>' . esc_html($table_name) . '</li>';
        }
        echo '</ul>';
        
        echo '<form method="post">';
        echo '<input type="hidden" name="action" value="delete_tables">';
        echo '<input type="submit" value="Delete Heritage Press Tables" onclick="return confirm(\'Are you sure? This will delete all genealogy data!\');">';
        echo '</form>';
    } else {
        echo '<p>✓ No Heritage Press tables found in database</p>';
    }
    
    // 4. Check plugin file status
    $plugin_dirs = [
        WP_CONTENT_DIR . '/plugins/heritage-press/',
        WP_CONTENT_DIR . '/plugins/heritage-press-1.0.0/',
        WP_CONTENT_DIR . '/plugins/heritage-press-1.0.0-1/',
        WP_CONTENT_DIR . '/plugins/heritage-press-1.0.0-2/',
    ];
    
    echo '<h3>Plugin Directory Status:</h3>';
    foreach ($plugin_dirs as $dir) {
        if (is_dir($dir)) {
            echo '<p>❌ Found directory: ' . esc_html($dir) . '</p>';
            echo '<p><em>Please manually delete this directory via FTP/cPanel File Manager</em></p>';
        } else {
            echo '<p>✓ Directory not found: ' . esc_html(basename($dir)) . '</p>';
        }
    }
    
    // 5. Clear any cached plugin data
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
        echo '<p>✓ Cleared WordPress cache</p>';
    }
    
    // 6. Clear plugin cache
    delete_site_transient('update_plugins');
    delete_transient('plugin_slugs');
    echo '<p>✓ Cleared plugin cache</p>';
}

// Handle form submissions
if ($_POST['action'] ?? '') {
    if ($_POST['action'] === 'delete_options') {
        $heritage_options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%heritage%' OR option_name LIKE '%Heritage%'"
        );
        
        foreach ($heritage_options as $option) {
            delete_option($option->option_name);
        }
        echo '<p><strong>✓ Deleted all Heritage Press options</strong></p>';
    }
    
    if ($_POST['action'] === 'delete_tables') {
        $tables = $wpdb->get_results(
            "SHOW TABLES LIKE '{$wpdb->prefix}heritage_%'"
        );
        
        foreach ($tables as $table) {
            $table_name = array_values((array)$table)[0];
            $wpdb->query("DROP TABLE IF EXISTS `$table_name`");
        }
        echo '<p><strong>✓ Deleted all Heritage Press tables</strong></p>';
    }
}

// Run the cleanup
cleanup_heritage_press_plugin();

echo '<h2>Manual Steps Required:</h2>';
echo '<ol>';
echo '<li>Delete any heritage-press* folders from /wp-content/plugins/ via FTP or cPanel File Manager</li>';
echo '<li>Go to WordPress Admin → Plugins page and refresh</li>';
echo '<li>Install the new heritage-press.zip file</li>';
echo '<li>Delete this cleanup script file for security</li>';
echo '</ol>';

echo '<h2>Installation Commands:</h2>';
echo '<p>After cleanup, install using the new file:</p>';
echo '<code>heritage-press.zip</code> (NOT heritage-press-1.0.0.zip)';

?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #0073aa; }
code { background: #f1f1f1; padding: 2px 4px; }
form { margin: 10px 0; }
input[type="submit"] { padding: 8px 16px; background: #0073aa; color: white; border: none; cursor: pointer; }
input[type="submit"]:hover { background: #005a87; }
</style>
