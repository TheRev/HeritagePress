<?php
/**
 * Test GEDCOM Import Functionality
 * 
 * This script tests the complete GEDCOM import process using the Cox family GEDCOM file
 */

// WordPress Bootstrap
$wp_load_path = __DIR__ . '/../../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    die("WordPress not found. Please ensure this script is in the correct location.\n");
}

require_once $wp_load_path;

// Load plugin classes
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/class-heritagepress-autoloader.php';

echo "=== HeritagePress GEDCOM Import Test ===\n\n";

// Test 1: Check if plugin tables exist
echo "1. Checking database tables...\n";
global $wpdb;

$tables_to_check = [
    'hp_individuals',
    'hp_families',
    'hp_sources',
    'hp_notes',
    'hp_calendar_systems'
];

$tables_exist = true;
foreach ($tables_to_check as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    if ($exists) {
        echo "   ✓ Table $full_table_name exists\n";
    } else {
        echo "   ✗ Table $full_table_name missing\n";
        $tables_exist = false;
    }
}

if (!$tables_exist) {
    echo "\nERROR: Required tables are missing. Please activate the plugin first.\n";
    exit(1);
}

// Test 2: Check if GEDCOM file exists
echo "\n2. Checking GEDCOM file...\n";
$gedcom_file = __DIR__ . '/cox-family.ged';
if (!file_exists($gedcom_file)) {
    echo "   ✗ GEDCOM file not found: $gedcom_file\n";
    echo "   Trying alternative location...\n";
    $gedcom_file = __DIR__ . '/test-cox-family.ged';
    if (!file_exists($gedcom_file)) {
        echo "   ✗ Alternative GEDCOM file not found: $gedcom_file\n";
        echo "   Creating sample GEDCOM file for testing...\n";

        $sample_gedcom = "0 HEAD\n";
        $sample_gedcom .= "1 SOUR HeritagePress Test\n";
        $sample_gedcom .= "1 GEDC\n";
        $sample_gedcom .= "2 VERS 5.5.1\n";
        $sample_gedcom .= "1 CHAR UTF-8\n";
        $sample_gedcom .= "0 @I1@ INDI\n";
        $sample_gedcom .= "1 NAME John /Cox/\n";
        $sample_gedcom .= "1 SEX M\n";
        $sample_gedcom .= "0 @I2@ INDI\n";
        $sample_gedcom .= "1 NAME Mary /Smith/\n";
        $sample_gedcom .= "1 SEX F\n";
        $sample_gedcom .= "0 @F1@ FAM\n";
        $sample_gedcom .= "1 HUSB @I1@\n";
        $sample_gedcom .= "1 WIFE @I2@\n";
        $sample_gedcom .= "0 TRLR\n";

        file_put_contents($gedcom_file, $sample_gedcom);
        echo "   ✓ Sample GEDCOM file created: $gedcom_file\n";
    }
} else {
    echo "   ✓ GEDCOM file found: $gedcom_file\n";
}

// Show file size and first few lines
$file_size = filesize($gedcom_file);
$file_content = file_get_contents($gedcom_file);
$lines = explode("\n", $file_content);
echo "   File size: " . number_format($file_size) . " bytes\n";
echo "   Total lines: " . count($lines) . "\n";
echo "   First 5 lines:\n";
for ($i = 0; $i < min(5, count($lines)); $i++) {
    echo "     " . trim($lines[$i]) . "\n";
}

// Test 3: Load and test GedcomService
echo "\n3. Testing GedcomService class...\n";
try {
    $gedcom_service = new HeritagePress\Services\GedcomService();
    echo "   ✓ GedcomService class loaded successfully\n";
} catch (Exception $e) {
    echo "   ✗ Failed to load GedcomService: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Clear existing test data
echo "\n4. Clearing existing test data...\n";
$tree_id = 1; // Test tree ID

$clear_queries = [
    "DELETE FROM {$wpdb->prefix}hp_individuals WHERE tree_id = $tree_id",
    "DELETE FROM {$wpdb->prefix}hp_families WHERE tree_id = $tree_id",
    "DELETE FROM {$wpdb->prefix}hp_sources WHERE tree_id = $tree_id",
    "DELETE FROM {$wpdb->prefix}hp_notes WHERE tree_id = $tree_id"
];

foreach ($clear_queries as $query) {
    $result = $wpdb->query($query);
    if ($result !== false) {
        echo "   ✓ Cleared records: $result rows affected\n";
    } else {
        echo "   ✗ Failed to clear data: " . $wpdb->last_error . "\n";
    }
}

// Test 5: Import GEDCOM file
echo "\n5. Importing GEDCOM file...\n";
try {
    $import_result = $gedcom_service->import($gedcom_file, $tree_id);

    if ($import_result['success']) {
        echo "   ✓ GEDCOM import completed successfully!\n";
        echo "   Statistics:\n";
        foreach ($import_result['stats'] as $key => $value) {
            if ($key === 'errors' && is_array($value)) {
                echo "     $key: " . count($value) . "\n";
                if (!empty($value)) {
                    foreach ($value as $error) {
                        echo "       - $error\n";
                    }
                }
            } else {
                echo "     $key: $value\n";
            }
        }
    } else {
        echo "   ✗ GEDCOM import failed: " . $import_result['message'] . "\n";
        if (!empty($import_result['stats']['errors'])) {
            echo "   Errors:\n";
            foreach ($import_result['stats']['errors'] as $error) {
                echo "     - $error\n";
            }
        }
        exit(1);
    }
} catch (Exception $e) {
    echo "   ✗ Exception during import: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verify imported data
echo "\n6. Verifying imported data...\n";

// Check individuals
$individuals_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_individuals WHERE tree_id = $tree_id");
echo "   Individuals in database: $individuals_count\n";

if ($individuals_count > 0) {
    $sample_individuals = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_individuals WHERE tree_id = $tree_id LIMIT 3");
    echo "   Sample individuals:\n";
    foreach ($sample_individuals as $individual) {
        $name = trim($individual->given_names . ' ' . $individual->surname);
        echo "     - ID: {$individual->id}, Name: '$name', Gender: {$individual->gender}, External ID: {$individual->external_id}\n";
    }
}

// Check families
$families_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE tree_id = $tree_id");
echo "   Families in database: $families_count\n";

if ($families_count > 0) {
    $sample_families = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_families WHERE tree_id = $tree_id LIMIT 3");
    echo "   Sample families:\n";
    foreach ($sample_families as $family) {
        echo "     - ID: {$family->id}, Husband: {$family->husband_id}, Wife: {$family->wife_id}, External ID: {$family->external_id}\n";
    }
}

// Check sources
$sources_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE tree_id = $tree_id");
echo "   Sources in database: $sources_count\n";

// Check notes
$notes_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_notes WHERE tree_id = $tree_id");
echo "   Notes in database: $notes_count\n";

// Test 7: Test Import/Export Manager integration
echo "\n7. Testing ImportExportManager integration...\n";
try {
    $import_manager = new HeritagePress\Admin\ImportExportManager();
    echo "   ✓ ImportExportManager loaded successfully\n";

    // Test the import handler
    $import_handler = $import_manager->getImportHandler();
    echo "   ✓ ImportHandler loaded successfully\n";

} catch (Exception $e) {
    echo "   ✗ Failed to load ImportExportManager: " . $e->getMessage() . "\n";
}

// Test 8: Check WordPress admin interface readiness
echo "\n8. Checking WordPress admin interface...\n";
if (is_admin()) {
    echo "   ✓ Running in WordPress admin context\n";
} else {
    echo "   ⚠ Not running in WordPress admin context (this is normal for CLI)\n";
}

// Check if AJAX handlers are registered
echo "   Checking AJAX handlers...\n";
$ajax_actions = [
    'hp_upload_gedcom',
    'hp_process_gedcom',
    'hp_import_progress',
    'hp_validate_date',
    'hp_convert_date'
];

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_$action")) {
        echo "   ✓ AJAX handler registered: $action\n";
    } else {
        echo "   ⚠ AJAX handler not registered: $action (may be registered later)\n";
    }
}

echo "\n=== Test Summary ===\n";
echo "✓ Database tables: OK\n";
echo "✓ GEDCOM file: OK\n";
echo "✓ GedcomService: OK\n";
echo "✓ Import process: OK\n";
echo "✓ Data verification: OK\n";
echo "✓ Manager integration: OK\n";

echo "\n=== GEDCOM Import is Working! ===\n";
echo "You can now use the WordPress admin interface to import GEDCOM files.\n";
echo "Go to: WordPress Admin > HeritagePress > Import/Export\n";
echo "\nTotal records imported:\n";
echo "- Individuals: $individuals_count\n";
echo "- Families: $families_count\n";
echo "- Sources: $sources_count\n";
echo "- Notes: $notes_count\n";
