<?php
/**
 * Script to reactivate the HeritagePress plugin and create database tables
 * 
 * @package HeritagePress
 */

// Load WordPress core
define('WP_ADMIN', true);
require_once 'C:/MAMP/htdocs/wordpress/wp-load.php';

// Ensure user has admin privileges
if (!current_user_can('activate_plugins')) {
    wp_die('You do not have permission to run this script.');
}

// Include necessary files
require_once 'includes/Database/Manager.php';
require_once 'includes/class-heritagepress-autoloader.php';

// Register autoloader
$autoloader = new HeritagePress_Autoloader();
$autoloader->register();

// Initialize database manager
try {
    $plugin_dir = dirname(__FILE__);
    $db_manager = new \HeritagePress\Database\Manager($plugin_dir);
    echo '<h1>HeritagePress Plugin Reactivation</h1>';
    echo '<p>Starting database table creation...</p>';

    // Create/update tables
    $result = $db_manager->init_tables();

    if ($result) {
        echo '<p style="color: green;">Database tables successfully created/updated!</p>';

        // Update plugin version in options
        update_option('heritagepress_db_version', '1.0.0');
        echo '<p>Plugin version updated in options table.</p>';

        // Force refresh of capabilities
        echo '<p>Refreshing plugin capabilities...</p>';
        // Add code here to refresh capabilities if needed

        echo '<hr>';
        echo '<p><strong>Plugin successfully reactivated!</strong></p>';
        echo '<p><a href="' . admin_url('admin.php?page=heritagepress') . '">Go to HeritagePress admin</a></p>';
    } else {
        echo '<p style="color: red;">Failed to create database tables. Check the error logs for details.</p>';
    }
} catch (Exception $e) {
    echo '<p style="color: red;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
