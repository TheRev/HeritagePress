<?php
/**
 * Heritage Press Database Schema Verification
 * 
 * This script verifies the integrity of the database schema after removing
 * the Evidence Explained system.
 * 
 * Run this from the WordPress root directory:
 * php heritage-press-schema-verify.php
 * 
 * @package HeritagePress\Tools
 */

// Load WordPress environment
require_once(dirname(__FILE__) . '/../../../wp-load.php');

// Only allow running from WP-CLI or admin
if (!defined('WP_CLI') && !current_user_can('manage_options')) {
    die('Access denied: This script can only be run by administrators.');
}

echo "Heritage Press Database Schema Verification\n";
echo "===========================================\n\n";

// Check core tables
global $wpdb;
$table_prefix = $wpdb->prefix . 'heritage_press_';

$core_tables = [
    'individuals' => [
        'columns' => ['id', 'uuid', 'given_names', 'surname', 'sex', 'birth_date', 'birth_place_id', 'death_date']
    ],
    'families' => [
        'columns' => ['id', 'uuid', 'husband_id', 'wife_id', 'marriage_date', 'marriage_place_id']
    ],
    'family_relationships' => [
        'columns' => ['id', 'uuid', 'individual_id', 'family_id', 'relationship_type', 'pedigree_type']
    ],
    'sources' => [
        'columns' => ['id', 'uuid', 'source_type', 'title', 'author', 'publication_info']
    ],
    'citations' => [
        'columns' => ['id', 'uuid', 'source_id', 'page_number', 'quality_assessment', 'confidence_score']
    ],
    'events' => [
        'columns' => ['id', 'uuid', 'individual_id', 'family_id', 'event_tag', 'event_date', 'place_id']
    ],
    'places' => [
        'columns' => ['id', 'uuid', 'name', 'parent_id', 'latitude', 'longitude']
    ]
];

$all_good = true;

foreach ($core_tables as $table => $config) {
    $full_table_name = $table_prefix . $table;
    echo "Checking table $full_table_name...\n";
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
    
    if (!$table_exists) {
        echo "❌ Table $full_table_name not found!\n";
        $all_good = false;
        continue;
    }
    
    // Get columns
    $columns = [];
    $column_results = $wpdb->get_results("DESCRIBE $full_table_name");
    foreach ($column_results as $col) {
        $columns[] = $col->Field;
    }
    
    // Check for required columns
    $missing_columns = array_diff($config['columns'], $columns);
    if (!empty($missing_columns)) {
        echo "❌ Missing columns in $full_table_name: " . implode(', ', $missing_columns) . "\n";
        $all_good = false;
    } else {
        echo "✓ All required columns present\n";
    }
    
    // Check for foreign keys (if available)
    if ($wpdb->get_var("SHOW CREATE TABLE $full_table_name") !== null) {
        $create_table = $wpdb->get_row("SHOW CREATE TABLE $full_table_name", ARRAY_N);
        if (!empty($create_table[1])) {
            $has_constraints = strpos($create_table[1], 'CONSTRAINT') !== false;
            if ($has_constraints) {
                echo "✓ Table has foreign key constraints defined\n";
            } else {
                echo "⚠ Table does not have foreign key constraints\n";
            }
        }
    }
    
    // Check for data
    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
    echo "ℹ Table contains $row_count rows\n\n";
}

// Check for Evidence Explained tables (they should be gone)
$evidence_tables = [
    'research_questions',
    'information_statements',
    'evidence_analysis',
    'proof_arguments',
    'proof_evidence_links',
    'source_quality_assessments'
];

$evidence_remnants = false;
foreach ($evidence_tables as $table) {
    $full_table_name = $table_prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
    
    if ($table_exists) {
        echo "❌ Evidence Explained table $full_table_name still exists!\n";
        $evidence_remnants = true;
        $all_good = false;
    }
}

if (!$evidence_remnants) {
    echo "✓ No Evidence Explained tables found (correct)\n\n";
}

// Overall results
if ($all_good) {
    echo "===========================================\n";
    echo "✅ Database schema verification successful!\n";
    echo "All core tables are present with required columns.\n";
    echo "No Evidence Explained tables were found.\n";
    echo "The database schema has been successfully transitioned to a standard genealogy model.\n";
} else {
    echo "===========================================\n";
    echo "⚠ Database schema verification found issues.\n";
    echo "Please review the warnings above and take appropriate action.\n";
    echo "You may need to restore from a backup or repair the database.\n";
}

// Output final recommendation
echo "\nRecommended next steps:\n";
echo "1. If all checks passed, continue using Heritage Press normally.\n";
echo "2. If issues were found, run the Health Check tool in the admin interface.\n";
echo "3. Consider backing up your database before making any changes.\n";
