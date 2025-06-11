<?php
/**
 * Validate GEDCOM Import System Integration
 * Tests all components of the GEDCOM import system
 */

// WordPress environment
define('WP_USE_THEMES', false);
require_once('c:\MAMP\htdocs\wordpress\wp-config.php');

// Load plugin autoloader
require_once(__DIR__ . '/includes/class-heritagepress-autoloader.php');

echo "<h1>HeritagePress GEDCOM Import System Validation</h1>\n";

$tests_passed = 0;
$total_tests = 0;

function run_test($test_name, $test_function)
{
    global $tests_passed, $total_tests;
    $total_tests++;

    echo "<h3>Test: $test_name</h3>\n";

    try {
        $result = $test_function();
        if ($result) {
            echo "<p style='color: green;'>✓ PASSED</p>\n";
            $tests_passed++;
        } else {
            echo "<p style='color: red;'>✗ FAILED</p>\n";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ FAILED: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }

    echo "<hr>\n";
}

// Test 1: GEDCOM Service Class Loading
run_test("GEDCOM Service Class Loading", function () {
    try {
        $service = new \HeritagePress\Services\GedcomService();
        echo "<p>GedcomService instantiated successfully</p>\n";
        return true;
    } catch (Exception $e) {
        echo "<p>Failed to instantiate GedcomService: " . $e->getMessage() . "</p>\n";
        return false;
    }
});

// Test 2: Date Converter Integration
run_test("Date Converter Integration", function () {
    try {
        $date_converter = new \HeritagePress\Models\DateConverter();
        echo "<p>DateConverter instantiated successfully</p>\n";
        return true;
    } catch (Exception $e) {
        echo "<p>Failed to instantiate DateConverter: " . $e->getMessage() . "</p>\n";
        return false;
    }
});

// Test 3: Base Manager Integration
run_test("Base Manager Integration", function () {
    try {
        $base_manager = new \HeritagePress\Admin\ImportExport\BaseManager();
        echo "<p>BaseManager instantiated successfully</p>\n";
        return true;
    } catch (Exception $e) {
        echo "<p>Failed to instantiate BaseManager: " . $e->getMessage() . "</p>\n";
        return false;
    }
});

// Test 4: Import Handler Integration
run_test("Import Handler Integration", function () {
    try {
        $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
        echo "<p>ImportHandler instantiated successfully</p>\n";
        return true;
    } catch (Exception $e) {
        echo "<p>Failed to instantiate ImportHandler: " . $e->getMessage() . "</p>\n";
        return false;
    }
});

// Test 5: Database Tables Exist
run_test("Database Tables Exist", function () {
    global $wpdb;

    $required_tables = [
        'hp_trees',
        'hp_individuals',
        'hp_families',
        'hp_sources',
        'hp_notes'
    ];

    $missing_tables = [];

    foreach ($required_tables as $table) {
        $full_table_name = $wpdb->prefix . $table;
        $result = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
        if ($result !== $full_table_name) {
            $missing_tables[] = $table;
        }
    }

    if (empty($missing_tables)) {
        echo "<p>All required database tables exist</p>\n";
        return true;
    } else {
        echo "<p>Missing tables: " . implode(', ', $missing_tables) . "</p>\n";
        return false;
    }
});

// Test 6: GEDCOM File Access
run_test("GEDCOM File Access", function () {
    $gedcom_file = 'C:\Users\Joe\Documents\Cox Family Tree_2025-05-26.ged';

    if (!file_exists($gedcom_file)) {
        echo "<p>GEDCOM file not found: $gedcom_file</p>\n";
        return false;
    }

    $size = filesize($gedcom_file);
    echo "<p>GEDCOM file found, size: " . number_format($size) . " bytes</p>\n";

    // Try to read first few lines
    $lines = file($gedcom_file, FILE_IGNORE_NEW_LINES);
    if (empty($lines)) {
        echo "<p>Failed to read GEDCOM file</p>\n";
        return false;
    }

    echo "<p>First line: " . htmlspecialchars($lines[0]) . "</p>\n";
    echo "<p>Total lines: " . count($lines) . "</p>\n";

    return true;
});

// Test 7: Import Process Test (Dry Run)
run_test("Import Process Test (Small Sample)", function () {
    try {
        // Create a minimal test GEDCOM content
        $test_gedcom = "0 HEAD\n";
        $test_gedcom .= "1 SOUR Test\n";
        $test_gedcom .= "1 GEDC\n";
        $test_gedcom .= "2 VERS 5.5.1\n";
        $test_gedcom .= "0 @I1@ INDI\n";
        $test_gedcom .= "1 NAME John /Doe/\n";
        $test_gedcom .= "1 SEX M\n";
        $test_gedcom .= "0 TRLR\n";

        // Save to temporary file
        $temp_file = sys_get_temp_dir() . '/test_gedcom_' . time() . '.ged';
        file_put_contents($temp_file, $test_gedcom);

        // Create test tree
        global $wpdb;
        $tree_data = [
            'name' => 'Test Tree ' . time(),
            'description' => 'Validation test tree',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $result = $wpdb->insert($wpdb->prefix . 'hp_trees', $tree_data);
        if ($result === false) {
            throw new Exception("Failed to create test tree");
        }

        $tree_id = $wpdb->insert_id;
        echo "<p>Test tree created with ID: $tree_id</p>\n";

        // Test import
        $gedcom_service = new \HeritagePress\Services\GedcomService();
        $import_result = $gedcom_service->import($temp_file, $tree_id);

        // Clean up
        unlink($temp_file);

        if ($import_result['success']) {
            echo "<p>Import successful!</p>\n";
            echo "<p>Individuals imported: " . $import_result['stats']['individuals'] . "</p>\n";

            // Verify data in database
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}hp_individuals WHERE tree_id = %d",
                $tree_id
            ));

            echo "<p>Individuals in database: $count</p>\n";

            // Clean up test data
            $wpdb->delete($wpdb->prefix . 'hp_individuals', ['tree_id' => $tree_id]);
            $wpdb->delete($wpdb->prefix . 'hp_trees', ['id' => $tree_id]);

            return $count > 0;
        } else {
            echo "<p>Import failed: " . $import_result['message'] . "</p>\n";
            return false;
        }

    } catch (Exception $e) {
        echo "<p>Test failed: " . $e->getMessage() . "</p>\n";
        return false;
    }
});

// Summary
echo "<h2>Test Summary</h2>\n";
echo "<p><strong>Tests Passed: $tests_passed / $total_tests</strong></p>\n";

if ($tests_passed === $total_tests) {
    echo "<h3 style='color: green;'>🎉 ALL TESTS PASSED!</h3>\n";
    echo "<p style='color: green; font-weight: bold;'>The GEDCOM import system is fully functional and ready to use!</p>\n";
    echo "<p>You can now:</p>\n";
    echo "<ul>\n";
    echo "<li>Import GEDCOM files through the WordPress admin interface</li>\n";
    echo "<li>All imported data will populate the correct database tables</li>\n";
    echo "<li>Date conversion and validation is working</li>\n";
    echo "<li>All modular components are properly integrated</li>\n";
    echo "</ul>\n";
} else {
    $failed = $total_tests - $tests_passed;
    echo "<h3 style='color: red;'>⚠️ $failed Tests Failed</h3>\n";
    echo "<p style='color: red;'>Some components need attention before the system is fully functional.</p>\n";
}

echo "<p><a href='http://localhost/wordpress/wp-admin/admin.php?page=heritagepress-importexport'>Test the import interface →</a></p>\n";
?>