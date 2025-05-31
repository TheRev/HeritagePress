<?php
/**
 * WordPress Heritage Press Path Fix
 * This script fixes the WordPress database entries pointing to the wrong plugin path
 * 
 * Upload this to your WordPress root and run it once
 * URL: http://yoursite.com/heritage-press-path-fix.php
 */

// Load WordPress
if (!file_exists('./wp-config.php')) {
    die('Error: Place this file in your WordPress root directory');
}

require_once('./wp-config.php');
global $wpdb;

echo '<h1>Heritage Press Path Fix</h1>';
echo '<style>body{font-family:Arial,sans-serif;margin:40px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>';

// Check current active plugins
$active_plugins = get_option('active_plugins', array());

echo '<h2>Current Active Plugins:</h2>';
echo '<ul>';
foreach ($active_plugins as $plugin) {
    echo '<li>' . $plugin;
    if (strpos($plugin, 'heritage-press') !== false) {
        echo ' <span class="error">← HERITAGE PRESS FOUND</span>';
    }
    echo '</li>';
}
echo '</ul>';

// Look for incorrect Heritage Press entries
$heritage_entries = array_filter($active_plugins, function($plugin) {
    return strpos($plugin, 'heritage-press') !== false;
});

if (!empty($heritage_entries)) {
    echo '<h2>Heritage Press Entries Found:</h2>';
    echo '<ul>';
    foreach ($heritage_entries as $plugin) {
        echo '<li class="error">' . $plugin . '</li>';
    }
    echo '</ul>';
    
    if (isset($_GET['fix']) && $_GET['fix'] === 'yes') {
        echo '<h2>Fixing Plugin Paths...</h2>';
        
        // Remove all Heritage Press entries
        $new_active = array_filter($active_plugins, function($plugin) {
            return strpos($plugin, 'heritage-press') === false;
        });
        
        // Add the correct Heritage Press entry
        $new_active[] = 'heritage-press/heritage-press.php';
        
        // Update the database
        update_option('active_plugins', $new_active);
        
        echo '<p class="success">✓ Fixed plugin path to: heritage-press/heritage-press.php</p>';
        echo '<p class="info">Go to WordPress Admin → Plugins to verify the fix</p>';
        
        // Clear any WordPress caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            echo '<p class="success">✓ WordPress cache cleared</p>';
        }
        
    } else {
        echo '<h2>Fix Required</h2>';
        echo '<p class="error">WordPress is looking for Heritage Press at the wrong path.</p>';
        echo '<p><strong><a href="?fix=yes" style="background:green;color:white;padding:10px;text-decoration:none;">FIX PLUGIN PATH</a></strong></p>';
    }
    
} else {
    echo '<h2>No Heritage Press Entries Found</h2>';
    echo '<p class="info">Heritage Press is not currently active in WordPress.</p>';
    echo '<p>You can now upload and activate the plugin normally.</p>';
}

// Check plugin file existence
echo '<h2>Plugin File Check:</h2>';
$plugin_file = WP_CONTENT_DIR . '/plugins/heritage-press/heritage-press.php';
if (file_exists($plugin_file)) {
    echo '<p class="success">✓ Plugin file exists at: ' . $plugin_file . '</p>';
} else {
    echo '<p class="error">✗ Plugin file NOT found at: ' . $plugin_file . '</p>';
    echo '<p class="info">Upload the heritage-press.zip file via WordPress Admin → Plugins → Add New</p>';
}

echo '<hr>';
echo '<p><strong>After running this fix:</strong></p>';
echo '<ol>';
echo '<li>Delete this script from your server</li>';
echo '<li>Go to WordPress Admin → Plugins</li>';
echo '<li>Upload heritage-press.zip if not already uploaded</li>';
echo '<li>Activate Heritage Press plugin</li>';
echo '</ol>';

?>
