<?php
// Script to clear menu cache and capabilities
require_once dirname(dirname(dirname(__FILE__))) . '/wp-load.php';

// Clear menu-related transients
delete_transient('plugin_menu_transient');
delete_site_transient('plugin_menu_transient');

// Delete any menu-related user meta
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key LIKE '%menu%' OR meta_key LIKE '%capabilities%'");

// Clear user cache
wp_cache_flush();

echo "Menu cache cleared! Please refresh your WordPress admin page.";
