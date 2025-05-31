<?php
/**
 * WordPress Plugin Path Fix Tool
 * Use this script to fix database entries that point to wrong plugin paths
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    $wp_load_paths = [
        dirname(__FILE__) . '/wp-load.php',
        dirname(__FILE__) . '/../wp-load.php',
        dirname(__FILE__) . '/../../wp-load.php',
        dirname(__FILE__) . '/../../../wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $wp_load) {
        if (file_exists($wp_load)) {
            require_once $wp_load;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress not found. Place this file in your WordPress root directory.');
    }
}

echo "<h1>Heritage Press Plugin Path Fix Tool</h1>";
echo "<pre>";

// Check current plugin status
echo "=== Current Plugin Status ===\n";

// Get all plugins
$all_plugins = get_plugins();
$heritage_plugins = [];

foreach ($all_plugins as $plugin_file => $plugin_data) {
    if (strpos($plugin_data['Name'], 'Heritage') !== false || strpos($plugin_file, 'heritage') !== false) {
        $heritage_plugins[$plugin_file] = $plugin_data;
        echo "Found: $plugin_file - " . $plugin_data['Name'] . "\n";
    }
}

// Check active plugins
$active_plugins = get_option('active_plugins', []);
echo "\n=== Active Plugins Check ===\n";
$heritage_active = false;
foreach ($active_plugins as $active_plugin) {
    if (strpos($active_plugin, 'heritage') !== false) {
        echo "Active Heritage Plugin: $active_plugin\n";
        $heritage_active = true;
        
        // Check if the file actually exists
        $plugin_path = WP_PLUGIN_DIR . '/' . $active_plugin;
        if (file_exists($plugin_path)) {
            echo "✅ Plugin file exists: $plugin_path\n";
        } else {
            echo "❌ Plugin file missing: $plugin_path\n";
            echo "This is the source of your activation error!\n";
        }
    }
}

if (!$heritage_active) {
    echo "No Heritage Press plugins are currently active.\n";
}

// Look for incorrect database entries
echo "\n=== Database Cleanup ===\n";

// Check for duplicate or incorrect entries
$fixed_active_plugins = [];
$changes_made = false;

foreach ($active_plugins as $active_plugin) {
    if (strpos($active_plugin, 'heritage') !== false) {
        // Check for doubled paths like heritage-press/heritage-press/heritage-press.php
        if (substr_count($active_plugin, 'heritage-press') > 1) {
            echo "❌ Found incorrect path: $active_plugin\n";
            $correct_path = 'heritage-press/heritage-press.php';
            
            // Check if the correct path exists
            if (file_exists(WP_PLUGIN_DIR . '/' . $correct_path)) {
                echo "✅ Correcting to: $correct_path\n";
                $fixed_active_plugins[] = $correct_path;
                $changes_made = true;
            } else {
                echo "⚠️ Correct path not found, removing from active plugins\n";
                $changes_made = true;
            }
        } else {
            // Keep the existing entry if it's correct
            $plugin_path = WP_PLUGIN_DIR . '/' . $active_plugin;
            if (file_exists($plugin_path)) {
                $fixed_active_plugins[] = $active_plugin;
            } else {
                echo "❌ Removing non-existent plugin: $active_plugin\n";
                $changes_made = true;
            }
        }
    } else {
        // Keep non-heritage plugins as-is
        $fixed_active_plugins[] = $active_plugin;
    }
}

// Apply fixes if needed
if ($changes_made) {
    echo "\n=== Applying Fixes ===\n";
    
    // Update active plugins option
    $result = update_option('active_plugins', $fixed_active_plugins);
    if ($result) {
        echo "✅ Updated active plugins list\n";
    } else {
        echo "❌ Failed to update active plugins list\n";
    }
    
    // Clear any plugin caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
        echo "✅ Cleared WordPress cache\n";
    }
    
    // Delete transients related to plugins
    delete_transient('plugin_slugs');
    delete_site_transient('update_plugins');
    echo "✅ Cleared plugin transients\n";
    
} else {
    echo "No database fixes needed.\n";
}

// Final status check
echo "\n=== Final Status ===\n";
$final_active = get_option('active_plugins', []);
$heritage_found = false;

foreach ($final_active as $plugin) {
    if (strpos($plugin, 'heritage') !== false) {
        echo "Active Heritage Plugin: $plugin\n";
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;
        if (file_exists($plugin_path)) {
            echo "✅ Plugin file exists and is properly registered\n";
            $heritage_found = true;
        } else {
            echo "❌ Plugin file still missing\n";
        }
    }
}

if (!$heritage_found) {
    echo "No Heritage Press plugins are active after cleanup.\n";
    echo "\n=== Next Steps ===\n";
    echo "1. Go to WordPress Admin > Plugins\n";
    echo "2. Look for Heritage Press in the plugin list\n";
    echo "3. Click 'Activate' if it appears\n";
    echo "4. If it doesn't appear, re-upload the plugin zip file\n";
}

echo "\n=== Plugin Directory Contents ===\n";
$heritage_dir = WP_PLUGIN_DIR . '/heritage-press';
if (is_dir($heritage_dir)) {
    echo "Heritage Press directory contents:\n";
    $items = scandir($heritage_dir);
    foreach ($items as $item) {
        if ($item != '.' && $item != '..') {
            $item_path = $heritage_dir . '/' . $item;
            if (is_dir($item_path)) {
                echo "  📁 $item/\n";
            } else {
                echo "  📄 $item\n";
            }
        }
    }
} else {
    echo "Heritage Press directory not found at: $heritage_dir\n";
}

echo "</pre>";
?>
