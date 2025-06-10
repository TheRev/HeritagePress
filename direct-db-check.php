<?php
/**
 * Direct Database Check for HeritagePress Tables
 * Bypasses WordPress loading to directly check database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Direct Database Check for HeritagePress ===\n";

// Database connection details (adjust if needed)
$host = 'localhost';
$username = 'root';
$password = 'root';  // Default MAMP password
$database = 'wordpress';  // Adjust if your database name is different

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Database connection successful\n";
    echo "Database: $database\n\n";

    // Get table prefix from wp_options table
    $stmt = $pdo->query("SELECT option_value FROM wp_options WHERE option_name = 'wp_table_prefix' LIMIT 1");
    $prefix_result = $stmt->fetch();

    // If not found, try to detect from existing tables
    if (!$prefix_result) {
        $stmt = $pdo->query("SHOW TABLES LIKE '%_options'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($tables)) {
            $options_table = $tables[0];
            $prefix = str_replace('options', '', $options_table);
        } else {
            $prefix = 'wp_';  // Default fallback
        }
    } else {
        $prefix = $prefix_result['option_value'];
    }

    echo "Table prefix: $prefix\n\n";

    // HeritagePress tables to check
    $hp_tables = [
        // Core tables
        'hp_individuals',
        'hp_families',
        'hp_sources',
        'hp_citations',
        'hp_events',
        'hp_places',
        'hp_media',
        'hp_repositories',
        'hp_notes',

        // GEDCOM 7 tables  
        'hp_gedcom_files',
        'hp_gedcom_records',
        'hp_gedcom_structures',
        'hp_gedcom_tags',
        'hp_gedcom_values',
        'hp_gedcom_cross_references',
        'hp_gedcom_extensions',
        'hp_gedcom_metadata',
        'hp_gedcom_validation',

        // Compliance tables
        'hp_compliance_checks',
        'hp_compliance_issues',
        'hp_compliance_rules',
        'hp_extended_characters',
        'hp_media_links',
        'hp_calendar_conversions',

        // Documentation tables
        'hp_documentation_pages',
        'hp_documentation_sections',
        'hp_documentation_links',
        'hp_user_guides',
        'hp_api_documentation',
        'hp_changelog_entries',
        'hp_configuration_options',
        'hp_system_requirements'
    ];

    $existing = [];
    $missing = [];

    foreach ($hp_tables as $table) {
        $full_name = $prefix . $table;
        $stmt = $pdo->query("SHOW TABLES LIKE '$full_name'");

        if ($stmt->rowCount() > 0) {
            $existing[] = $table;
            echo "✓ $full_name\n";
        } else {
            $missing[] = $table;
            echo "✗ $full_name\n";
        }
    }

    echo "\n=== SUMMARY ===\n";
    echo "Expected tables: " . count($hp_tables) . "\n";
    echo "Existing tables: " . count($existing) . "\n";
    echo "Missing tables: " . count($missing) . "\n";

    if (count($missing) > 0) {
        echo "\nMissing tables:\n";
        foreach ($missing as $table) {
            echo "- {$prefix}{$table}\n";
        }
    }

    if (count($existing) == count($hp_tables)) {
        echo "\n🎉 SUCCESS: All HeritagePress tables found!\n";
    } else {
        echo "\n⚠️  INCOMPLETE: " . count($missing) . " tables are missing\n";
    }

} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "- MAMP is running\n";
    echo "- MySQL service is started\n";
    echo "- Database credentials are correct\n";
    echo "- Database '$database' exists\n";
}

echo "\n=== End Check ===\n";
