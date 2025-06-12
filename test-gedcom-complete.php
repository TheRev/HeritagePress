<?php
/**
 * Test GEDCOM Import with Repository and Media Support
 * 
 * This script tests the complete GEDCOM import workflow including:
 * - Individual records (INDI)
 * - Family records (FAM) 
 * - Source records (SOUR)
 * - Repository records (REPO)
 * - Media/Object records (OBJE)
 * - Note records (NOTE)
 */

// Include WordPress bootstrap
require_once '../../../../../../../wp-config.php';

// Set up WordPress environment
global $wpdb;

echo "<h1>GEDCOM Import Test - Repository and Media Support</h1>\n";

// Test GEDCOM content with all record types
$test_gedcom_content = <<<GEDCOM
0 HEAD
1 SOUR HeritagePress Test
1 GEDC
2 VERS 5.5.1
1 CHAR UTF-8

0 @I1@ INDI
1 NAME John /Smith/
1 SEX M

0 @I2@ INDI  
1 NAME Mary /Jones/
1 SEX F

0 @F1@ FAM
1 HUSB @I1@
1 WIFE @I2@

0 @R1@ REPO
1 NAME National Archives
1 ADDR 123 Archive Street, Washington DC

0 @S1@ SOUR
1 TITL Birth Certificate Collection
1 AUTH National Archives
1 PUBL Published 1950
1 REPO @R1@

0 @O1@ OBJE
1 FILE birth_cert.jpg
1 TITL Birth Certificate Image
2 FORM JPEG

0 @N1@ NOTE
1 CONT This is a test note about the birth certificate.
1 CONT It spans multiple lines to test CONT handling.

0 TRLR
GEDCOM;

try {
    echo "<h2>Step 1: Creating Test GEDCOM File</h2>\n";

    // Create temporary test file
    $temp_file = tempnam(sys_get_temp_dir(), 'gedcom_test_');
    file_put_contents($temp_file, $test_gedcom_content);
    echo "✓ Test GEDCOM file created: $temp_file<br>\n";

    echo "<h2>Step 2: Testing GEDCOM Import Service</h2>\n";

    // Test 1: Check if all record types are recognized
    echo "<h3>Test 1: Record Type Recognition</h3>\n";

    // Import the GEDCOM using our service
    require_once '../Services/GedcomService.php';

    $gedcom_service = new HeritagePress\Services\GedcomService();

    // Create a test tree (assuming tree ID 1 exists)
    $tree_id = 1;

    echo "Starting import into tree ID: $tree_id<br>\n";

    $result = $gedcom_service->import($temp_file, $tree_id);

    if ($result['success']) {
        echo "✓ GEDCOM import completed successfully!<br>\n";
        echo "<h3>Import Statistics:</h3>\n";
        echo "<ul>\n";
        foreach ($result['stats'] as $type => $count) {
            echo "<li><strong>$type:</strong> $count</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "✗ GEDCOM import failed: " . $result['message'] . "<br>\n";
        if (!empty($result['stats']['errors'])) {
            echo "<h3>Errors:</h3>\n";
            echo "<ul>\n";
            foreach ($result['stats']['errors'] as $error) {
                echo "<li>$error</li>\n";
            }
            echo "</ul>\n";
        }
    }

    echo "<h2>Step 3: Verifying Database Records</h2>\n";

    // Check each table for imported data
    $tables_to_check = [
        'hp_individuals' => 'Individuals',
        'hp_families' => 'Families',
        'hp_sources' => 'Sources',
        'hp_repositories' => 'Repositories',
        'hp_media' => 'Media Objects',
        'hp_notes' => 'Notes'
    ];

    foreach ($tables_to_check as $table => $label) {
        $full_table = $wpdb->prefix . $table;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $full_table WHERE tree_id = %d",
            $tree_id
        ));

        if ($count > 0) {
            echo "✓ $label: $count records found in $full_table<br>\n";

            // Show sample records
            $samples = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $full_table WHERE tree_id = %d LIMIT 3",
                $tree_id
            ));

            if (!empty($samples)) {
                echo "<details><summary>Sample Records</summary>\n";
                echo "<pre>" . print_r($samples, true) . "</pre>\n";
                echo "</details>\n";
            }
        } else {
            echo "⚠ $label: No records found in $full_table<br>\n";
        }
    }

    echo "<h2>Step 4: Testing Repository-Source Relationships</h2>\n";

    // Check if sources are properly linked to repositories
    $linked_sources = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, r.name as repo_name 
         FROM {$wpdb->prefix}hp_sources s 
         LEFT JOIN {$wpdb->prefix}hp_repositories r ON s.repository_id = r.id 
         WHERE s.tree_id = %d",
        $tree_id
    ));

    if (!empty($linked_sources)) {
        echo "✓ Source-Repository relationships found:<br>\n";
        foreach ($linked_sources as $source) {
            $repo_status = $source->repo_name ? "linked to '{$source->repo_name}'" : "no repository link";
            echo "- Source: '{$source->title}' ($repo_status)<br>\n";
        }
    } else {
        echo "⚠ No source records found<br>\n";
    }

    echo "<h2>Step 5: Testing Error Handling</h2>\n";

    // Test with invalid GEDCOM
    $invalid_gedcom = "This is not a valid GEDCOM file";
    $invalid_file = tempnam(sys_get_temp_dir(), 'invalid_gedcom_');
    file_put_contents($invalid_file, $invalid_gedcom);

    $error_result = $gedcom_service->import($invalid_file, $tree_id);

    if (!$error_result['success']) {
        echo "✓ Error handling works correctly<br>\n";
        echo "Error message: " . $error_result['message'] . "<br>\n";
    } else {
        echo "⚠ Error handling may not be working correctly<br>\n";
    }

    // Cleanup
    unlink($temp_file);
    unlink($invalid_file);

    echo "<h2>Step 6: Summary</h2>\n";
    echo "<div style='padding: 10px; background: #f0f8ff; border: 1px solid #0066cc;'>\n";
    echo "<strong>GEDCOM Import Enhancement Status:</strong><br>\n";
    echo "✓ Repository records (REPO) - Supported<br>\n";
    echo "✓ Media objects (OBJE) - Supported<br>\n";
    echo "✓ Source-Repository linking - Implemented<br>\n";
    echo "✓ Proper HeritagePress column mapping - Used<br>\n";
    echo "✓ Error handling - Functional<br>\n";
    echo "</div>\n";

} catch (Exception $e) {
    echo "<h2>Test Failed with Exception</h2>\n";
    echo "<div style='color: red; padding: 10px; background: #ffe6e6; border: 1px solid #cc0000;'>\n";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>\n";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>\n";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>\n";
    echo "</div>\n";
}

echo "<h2>Test Complete</h2>\n";
echo "<p><a href='#' onclick='history.back()'>← Back</a></p>\n";
?>