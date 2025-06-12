<?php
/**
 * Test GEDCOM import after external_id column fix
 */

require_once('../../../../../../wp-config.php');

echo "<h1>🧪 Testing GEDCOM Import After Schema Fix</h1>\n";

global $wpdb;

echo "<h2>1. 📋 Verify Database Schema</h2>\n";

// Check that external_id columns exist
$tables_to_check = ['hp_individuals', 'hp_families'];
$schema_ok = true;

foreach ($tables_to_check as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$full_table_name} LIKE 'external_id'");

    if ($column_exists) {
        echo "<p style='color: green;'>✅ {$full_table_name}.external_id exists</p>\n";
    } else {
        echo "<p style='color: red;'>❌ {$full_table_name}.external_id missing</p>\n";
        $schema_ok = false;
    }
}

if (!$schema_ok) {
    echo "<div style='background: #f8d7da; padding: 15px;'>\n";
    echo "<p><strong>❌ Schema check failed!</strong> Please run the fix-external-id-columns.php script first.</p>\n";
    echo "</div>\n";
    exit;
}

echo "<h2>2. 🔄 Test ImportHandler Initialization</h2>\n";

try {
    require_once(__DIR__ . '/includes/Admin/ImportExport/BaseManager.php');
    require_once(__DIR__ . '/includes/Admin/ImportExport/ImportHandler.php');

    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
    echo "<p style='color: green;'>✅ ImportHandler created successfully</p>\n";

    $gedcom_service = $import_handler->get_gedcom_service();
    echo "<p style='color: green;'>✅ GedcomService initialized successfully</p>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error initializing ImportHandler: " . $e->getMessage() . "</p>\n";
    exit;
}

echo "<h2>3. 📂 Check for GEDCOM Files</h2>\n";

$upload_info = wp_upload_dir();
$gedcom_dir = $upload_info['basedir'] . '/heritagepress/gedcom';

if (is_dir($gedcom_dir)) {
    $gedcom_files = glob($gedcom_dir . '/*.ged');
    if (!empty($gedcom_files)) {
        echo "<p style='color: green;'>✅ Found " . count($gedcom_files) . " GEDCOM file(s):</p>\n";
        foreach ($gedcom_files as $file) {
            echo "<li>" . basename($file) . " (" . round(filesize($file) / 1024, 1) . " KB)</li>\n";
        }
        $test_file = $gedcom_files[0];
    } else {
        echo "<p style='color: orange;'>⚠️ No GEDCOM files found in upload directory</p>\n";
        $test_file = null;
    }
} else {
    echo "<p style='color: red;'>❌ GEDCOM upload directory does not exist</p>\n";
    $test_file = null;
}

echo "<h2>4. 🗄️ Check Database Record Counts (Before)</h2>\n";

$before_individuals = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_individuals");
$before_families = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families");

echo "<table border='1' style='border-collapse: collapse;'>\n";
echo "<tr><th>Table</th><th>Records Before</th></tr>\n";
echo "<tr><td>Individuals</td><td>{$before_individuals}</td></tr>\n";
echo "<tr><td>Families</td><td>{$before_families}</td></tr>\n";
echo "</table>\n";

if ($test_file) {
    echo "<h2>5. 🧪 Test Import Process</h2>\n";

    try {
        // Simulate the import process
        $tree_id = 1; // Use a test tree ID
        $import_result = $gedcom_service->import($test_file, $tree_id);

        if ($import_result && isset($import_result['success']) && $import_result['success']) {
            echo "<p style='color: green;'>✅ Import completed successfully!</p>\n";
            echo "<p><strong>Import Stats:</strong></p>\n";
            if (isset($import_result['stats'])) {
                echo "<ul>\n";
                foreach ($import_result['stats'] as $key => $value) {
                    if (is_array($value)) {
                        echo "<li>{$key}: " . count($value) . " items</li>\n";
                    } else {
                        echo "<li>{$key}: {$value}</li>\n";
                    }
                }
                echo "</ul>\n";
            }
        } else {
            echo "<p style='color: red;'>❌ Import failed</p>\n";
            if (isset($import_result['message'])) {
                echo "<p><strong>Error:</strong> " . $import_result['message'] . "</p>\n";
            }
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Import exception: " . $e->getMessage() . "</p>\n";
        echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
    }

    echo "<h2>6. 🗄️ Check Database Record Counts (After)</h2>\n";

    $after_individuals = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_individuals");
    $after_families = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families");

    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Table</th><th>Before</th><th>After</th><th>Added</th></tr>\n";
    echo "<tr><td>Individuals</td><td>{$before_individuals}</td><td>{$after_individuals}</td><td>" . ($after_individuals - $before_individuals) . "</td></tr>\n";
    echo "<tr><td>Families</td><td>{$before_families}</td><td>{$after_families}</td><td>" . ($after_families - $before_families) . "</td></tr>\n";
    echo "</table>\n";

    if ($after_individuals > $before_individuals || $after_families > $before_families) {
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>\n";
        echo "<h3>🎉 Import Success!</h3>\n";
        echo "<p>Records were successfully added to the database.</p>\n";
        echo "<p><strong>The external_id column fix resolved the import issue!</strong></p>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>\n";
        echo "<h3>⚠️ No Records Added</h3>\n";
        echo "<p>The import process ran but no records were added to the database.</p>\n";
        echo "<p>This could indicate other issues that need investigation.</p>\n";
        echo "</div>\n";
    }

} else {
    echo "<div style='background: #f8d7da; padding: 15px;'>\n";
    echo "<p><strong>⚠️ Cannot test import:</strong> No GEDCOM files found.</p>\n";
    echo "<p>Upload a GEDCOM file first, then run this test again.</p>\n";
    echo "</div>\n";
}

echo "<h2>🎯 Summary</h2>\n";
echo "<p>The database schema has been fixed with external_id columns.</p>\n";
echo "<p>The import process should now work correctly via the web interface.</p>\n";
echo "<p><a href='/wordpress/wp-admin/admin.php?page=heritagepress-import&step=3'>Try Step 3 Import Again</a></p>\n";
?>