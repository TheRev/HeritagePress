<?php
/**
 * Database creation script for HeritagePress
 *
 * Use this script to create all HeritagePress database tables
 */

// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress - using absolute path to ensure we find it
require_once('C:/MAMP/htdocs/wordpress/wp-load.php');

// Check for admin privileges
if (!current_user_can('manage_options')) {
    die('You do not have sufficient permissions to access this page.');
}

// Add a header
echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">';
echo '<h1>HeritagePress Database Installation</h1>';
echo '<p>Creating HeritagePress database tables...</p>';

// Ensure required files are loaded
require_once dirname(__FILE__) . '/includes/class-heritagepress-autoloader.php';
HeritagePress_Autoloader::register();
require_once dirname(__FILE__) . '/includes/Database/Manager.php';
require_once dirname(__FILE__) . '/includes/Models/Model.php';
require_once dirname(__FILE__) . '/includes/Models/CalendarSystem.php';

// Initialize database manager
$db_manager = new \HeritagePress\Database\Manager(dirname(__FILE__) . '/', '1.0.0');

// Create the tables
try {
    echo '<p>Initializing database tables...</p>';
    $db_manager->init_tables();
    echo '<p>Database initialization completed.</p>';
} catch (\Exception $e) {
    echo '<div style="background-color: #f2dede; color: #a94442; padding: 15px; margin: 20px 0; border: 1px solid #ebccd1; border-radius: 4px;">';
    echo '<h2>Error</h2>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '</div>';
    die();
}

// Verify the tables were created
echo '<h2>Table Verification</h2>';

// Get list of required tables
$required_tables = $db_manager->get_required_tables();
$table_count = count($required_tables);

echo "<p>Verifying $table_count required tables...</p>";
echo '<ul style="list-style-type: none; padding-left: 10px;">';

global $wpdb;
$success_count = 0;
$missing_tables = [];

foreach ($required_tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $all_tables = $wpdb->get_col('SHOW TABLES');
    $table_exists = in_array($full_table_name, $all_tables, true);

    if ($table_exists) {
        echo "<li style='color: green; margin-bottom: 5px;'>✓ Table <strong>$full_table_name</strong> exists</li>";
        $success_count++;
    } else {
        echo "<li style='color: red; margin-bottom: 5px;'>✗ Table <strong>$full_table_name</strong> is missing</li>";
        $missing_tables[] = $full_table_name;
    }
}

echo '</ul>';

// Show summary
if ($success_count === $table_count) {
    echo '<div style="background-color: #dff0d8; color: #3c763d; padding: 15px; margin: 20px 0; border: 1px solid #d6e9c6; border-radius: 4px;">';
    echo '<h2>Success!</h2>';
    echo "<p>All $table_count database tables were created successfully.</p>";
    echo '<p>You can now use the HeritagePress plugin to import your genealogy data.</p>';
    echo '</div>';
} else {
    echo '<div style="background-color: #fcf8e3; color: #8a6d3b; padding: 15px; margin: 20px 0; border: 1px solid #faebcc; border-radius: 4px;">';
    echo '<h2>Warning</h2>';
    echo "<p>Only $success_count of $table_count tables were created.</p>";
    echo '<p>Missing tables:</p>';
    echo '<ul>';
    foreach ($missing_tables as $table) {
        echo "<li>$table</li>";
    }
    echo '</ul>';
    echo '</div>';
}

// Admin links
echo '<div style="margin-top: 30px;">';
echo '<a href="' . admin_url('admin.php?page=heritagepress') . '" style="display: inline-block; background-color: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;">Go to HeritagePress Admin</a>';
echo '<a href="' . admin_url('plugins.php') . '" style="display: inline-block; background-color: #444; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;">Manage Plugins</a>';
echo '</div>';

echo '</div>'; // Close main container
