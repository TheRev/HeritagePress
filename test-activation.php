<?php
/**
 * Simple Plugin Activation Test
 * 
 * This script simulates plugin activation to test database table creation
 * Access via: http://localhost/wordpress/wp-content/plugins/heritagepress/HeritagePress/test-activation.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress
$wp_load_path = '../../../wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die('WordPress not found. Make sure this plugin is in the correct directory.');
}

echo '<h1>HeritagePress Plugin Activation Test</h1>';
echo '<p>Testing table creation...</p>';

// Load plugin files
require_once(__DIR__ . '/includes/class-heritagepress.php');

echo '<h2>Before Activation</h2>';
global $wpdb;
$before_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo '<p>Found ' . count($before_tables) . ' HeritagePress tables before activation.</p>';

if (count($before_tables) > 0) {
    echo '<ul>';
    foreach ($before_tables as $table) {
        echo '<li>' . esc_html($table) . '</li>';
    }
    echo '</ul>';
}

echo '<h2>Running Activation</h2>';
try {
    // Run the activation method directly
    HeritagePress::activate();
    echo '<p style="color: green;">✓ Activation method executed successfully!</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">✗ Activation failed: ' . esc_html($e->getMessage()) . '</p>';
} catch (Error $e) {
    echo '<p style="color: red;">✗ Activation error: ' . esc_html($e->getMessage()) . '</p>';
}

echo '<h2>After Activation</h2>';
$after_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo '<p>Found ' . count($after_tables) . ' HeritagePress tables after activation.</p>';

if (count($after_tables) > 0) {
    echo '<ul>';
    foreach ($after_tables as $table) {
        echo '<li>' . esc_html($table) . '</li>';
    }
    echo '</ul>';
} else {
    echo '<p style="color: orange;">⚠️ No HeritagePress tables found. Check error logs for details.</p>';
}

echo '<h2>Expected Tables</h2>';
$expected_tables = [
    'hp_media_links',
    'hp_aliases',
    'hp_associations',
    'hp_events',
    'hp_families',
    'hp_individuals',
    'hp_notes',
    'hp_repositories',
    'hp_sources',
    'hp_multimedia',
    'hp_places',
    'hp_citations',
    'hp_tags',
    'hp_addresses',
    'hp_dates',
    'hp_names',
    'hp_attributes',
    'hp_submitters',
    'hp_headers',
    'hp_trailers',
    'hp_files',
    'hp_records',
    'hp_extensions',
    'hp_custom_tags',
    'hp_validation_rules',
    'hp_import_logs',
    'hp_export_logs',
    'hp_data_quality',
    'hp_user_preferences',
    'hp_system_settings',
    'hp_backup_metadata',
    'hp_change_log'
];

echo '<p>Expecting ' . count($expected_tables) . ' tables total:</p>';
echo '<ul>';
foreach ($expected_tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = in_array($full_table_name, $after_tables);
    $status = $exists ? '✓' : '✗';
    $color = $exists ? 'green' : 'red';
    echo '<li style="color: ' . $color . ';">' . $status . ' ' . esc_html($full_table_name) . '</li>';
}
echo '</ul>';

$success_count = 0;
foreach ($expected_tables as $table) {
    if (in_array($wpdb->prefix . $table, $after_tables)) {
        $success_count++;
    }
}

echo '<h2>Summary</h2>';
echo '<p><strong>' . $success_count . ' of ' . count($expected_tables) . ' tables created successfully.</strong></p>';

if ($success_count === count($expected_tables)) {
    echo '<p style="color: green; font-size: 18px;">🎉 ALL TABLES CREATED SUCCESSFULLY!</p>';
} elseif ($success_count > 0) {
    echo '<p style="color: orange; font-size: 18px;">⚠️ PARTIAL SUCCESS - Some tables missing.</p>';
} else {
    echo '<p style="color: red; font-size: 18px;">❌ NO TABLES CREATED - Check configuration.</p>';
}

echo '<hr>';
echo '<p><a href="' . admin_url() . '">← Back to WordPress Admin</a></p>';
?>