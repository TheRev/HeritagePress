<?php
/**
 * Test GEDCOM Import with Real File
 * Tests the complete import process with the Cox Family Tree GEDCOM file
 */

// WordPress environment
define('WP_USE_THEMES', false);
require_once('c:\MAMP\htdocs\wordpress\wp-config.php');

// Load plugin
require_once(__DIR__ . '/includes/class-heritagepress-autoloader.php');
require_once(__DIR__ . '/includes/Services/GedcomService.php');

echo "<h1>HeritagePress GEDCOM Import Test</h1>\n";
echo "<p>Testing with Cox Family Tree GEDCOM file...</p>\n";

try {
    // Copy the GEDCOM file to plugin directory for testing
    $source_file = 'C:\Users\Joe\Documents\Cox Family Tree_2025-05-26.ged';
    $test_file = __DIR__ . '/cox-family.ged';

    if (!file_exists($source_file)) {
        throw new Exception("Source GEDCOM file not found: $source_file");
    }

    if (!copy($source_file, $test_file)) {
        throw new Exception("Failed to copy GEDCOM file for testing");
    }

    echo "<p>✓ GEDCOM file copied successfully</p>\n";

    // Check file size and first few lines
    $file_size = filesize($test_file);
    echo "<p>File size: " . number_format($file_size) . " bytes</p>\n";

    $first_lines = array_slice(file($test_file), 0, 10);
    echo "<h3>First 10 lines of GEDCOM file:</h3>\n";
    echo "<pre>" . htmlspecialchars(implode('', $first_lines)) . "</pre>\n";

    // Create a test tree
    global $wpdb;
    $tree_table = $wpdb->prefix . 'hp_trees';

    // Insert test tree
    $tree_data = [
        'name' => 'Cox Family Tree Test',
        'description' => 'Test import of Cox family GEDCOM',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ];

    $result = $wpdb->insert($tree_table, $tree_data);
    if ($result === false) {
        throw new Exception("Failed to create test tree: " . $wpdb->last_error);
    }

    $tree_id = $wpdb->insert_id;
    echo "<p>✓ Test tree created with ID: $tree_id</p>\n";

    // Initialize GEDCOM service
    $gedcom_service = new \HeritagePress\Services\GedcomService();

    echo "<h3>Starting GEDCOM Import...</h3>\n";
    $start_time = microtime(true);

    // Import the GEDCOM file
    $result = $gedcom_service->import($test_file, $tree_id);

    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 2);

    echo "<h3>Import Results:</h3>\n";
    echo "<p>Duration: {$duration} seconds</p>\n";

    if ($result['success']) {
        echo "<p style='color: green;'>✓ Import completed successfully!</p>\n";
        echo "<h4>Statistics:</h4>\n";
        echo "<ul>\n";
        echo "<li>Individuals: " . $result['stats']['individuals'] . "</li>\n";
        echo "<li>Families: " . $result['stats']['families'] . "</li>\n";
        echo "<li>Sources: " . $result['stats']['sources'] . "</li>\n";
        echo "<li>Notes: " . $result['stats']['notes'] . "</li>\n";
        echo "<li>Events: " . $result['stats']['events'] . "</li>\n";
        echo "</ul>\n";

        if (!empty($result['stats']['errors'])) {
            echo "<h4>Errors encountered:</h4>\n";
            echo "<ul style='color: orange;'>\n";
            foreach ($result['stats']['errors'] as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>\n";
            }
            echo "</ul>\n";
        }

        // Verify data was inserted into database
        echo "<h3>Database Verification:</h3>\n";

        $individuals_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}hp_individuals WHERE tree_id = %d",
            $tree_id
        ));
        echo "<p>Individuals in database: $individuals_count</p>\n";

        $families_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE tree_id = %d",
            $tree_id
        ));
        echo "<p>Families in database: $families_count</p>\n";

        $sources_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE tree_id = %d",
            $tree_id
        ));
        echo "<p>Sources in database: $sources_count</p>\n";

        // Show sample individuals
        $sample_individuals = $wpdb->get_results($wpdb->prepare(
            "SELECT id, given_names, surname, gender, external_id FROM {$wpdb->prefix}hp_individuals WHERE tree_id = %d LIMIT 10",
            $tree_id
        ));

        if (!empty($sample_individuals)) {
            echo "<h4>Sample Individuals:</h4>\n";
            echo "<table border='1' cellpadding='5'>\n";
            echo "<tr><th>ID</th><th>Given Names</th><th>Surname</th><th>Gender</th><th>External ID</th></tr>\n";
            foreach ($sample_individuals as $individual) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($individual->id) . "</td>";
                echo "<td>" . htmlspecialchars($individual->given_names ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($individual->surname ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($individual->gender ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($individual->external_id ?? '') . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }

        // Show sample families
        $sample_families = $wpdb->get_results($wpdb->prepare(
            "SELECT f.id, f.external_id, h.given_names as husband_given, h.surname as husband_surname, 
                    w.given_names as wife_given, w.surname as wife_surname
             FROM {$wpdb->prefix}hp_families f 
             LEFT JOIN {$wpdb->prefix}hp_individuals h ON f.husband_id = h.id
             LEFT JOIN {$wpdb->prefix}hp_individuals w ON f.wife_id = w.id
             WHERE f.tree_id = %d LIMIT 5",
            $tree_id
        ));

        if (!empty($sample_families)) {
            echo "<h4>Sample Families:</h4>\n";
            echo "<table border='1' cellpadding='5'>\n";
            echo "<tr><th>ID</th><th>External ID</th><th>Husband</th><th>Wife</th></tr>\n";
            foreach ($sample_families as $family) {
                $husband_name = trim(($family->husband_given ?? '') . ' ' . ($family->husband_surname ?? ''));
                $wife_name = trim(($family->wife_given ?? '') . ' ' . ($family->wife_surname ?? ''));

                echo "<tr>";
                echo "<td>" . htmlspecialchars($family->id) . "</td>";
                echo "<td>" . htmlspecialchars($family->external_id ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($husband_name ?: 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($wife_name ?: 'N/A') . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }

        echo "<p style='color: green; font-weight: bold;'>🎉 GEDCOM Import Test PASSED!</p>\n";

    } else {
        echo "<p style='color: red;'>✗ Import failed: " . htmlspecialchars($result['message']) . "</p>\n";

        if (!empty($result['stats']['errors'])) {
            echo "<h4>Errors:</h4>\n";
            echo "<ul style='color: red;'>\n";
            foreach ($result['stats']['errors'] as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>\n";
            }
            echo "</ul>\n";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Test failed with exception: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "<h3>Test Complete</h3>\n";
echo "<p>Check the WordPress admin panel at <a href='http://localhost/wordpress/wp-admin/admin.php?page=heritagepress-individuals'>HeritagePress > Individuals</a> to see imported data.</p>\n";
?>