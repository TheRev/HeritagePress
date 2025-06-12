<?php
/**
 * Robust Column Fix with Better Error Handling
 */

// Load WordPress
require_once('../../../wp-config.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied.');
}

global $wpdb;

echo "<h1>Robust Column Fix</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Check if columns exist first
$column_checks = [
    'wp_hp_people' => ['person_id'],
    'wp_hp_families' => ['family_id'],
    'wp_hp_sources' => ['source_id'],
    'wp_hp_repositories' => ['name'],
    'wp_hp_media' => ['media_id']
];

echo "<h2>Pre-Fix Column Status</h2>";
foreach ($column_checks as $table => $columns) {
    $result = $wpdb->get_results("DESCRIBE $table");
    if ($result) {
        $existing_fields = array_column($result, 'Field');
        foreach ($columns as $column) {
            $exists = in_array($column, $existing_fields);
            $status = $exists ? "✅ EXISTS" : "❌ MISSING";
            echo "<p>$table.$column: $status</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Table $table: " . $wpdb->last_error . "</p>";
    }
}

echo "<hr><h2>Adding Missing Columns</h2>";

// Add columns with IF NOT EXISTS logic (MySQL 8.0+ compatible)
$fixes = [
    [
        'table' => 'wp_hp_people',
        'column' => 'person_id',
        'definition' => 'VARCHAR(50) NOT NULL DEFAULT \'\'',
        'position' => 'AFTER gedcom'
    ],
    [
        'table' => 'wp_hp_families',
        'column' => 'family_id',
        'definition' => 'VARCHAR(50) NOT NULL DEFAULT \'\'',
        'position' => 'AFTER gedcom'
    ],
    [
        'table' => 'wp_hp_sources',
        'column' => 'source_id',
        'definition' => 'VARCHAR(50) NOT NULL DEFAULT \'\'',
        'position' => 'AFTER gedcom'
    ],
    [
        'table' => 'wp_hp_repositories',
        'column' => 'name',
        'definition' => 'VARCHAR(255) NOT NULL DEFAULT \'\'',
        'position' => 'AFTER repo_id'
    ],
    [
        'table' => 'wp_hp_media',
        'column' => 'media_id',
        'definition' => 'VARCHAR(50) NOT NULL DEFAULT \'\'',
        'position' => 'AFTER gedcom'
    ]
];

foreach ($fixes as $fix) {
    echo "<p><strong>Processing: {$fix['table']}.{$fix['column']}</strong></p>";

    // Check if column exists first
    $check_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = '{$fix['table']}' 
                  AND COLUMN_NAME = '{$fix['column']}' 
                  AND TABLE_SCHEMA = DATABASE()";

    $exists = $wpdb->get_var($check_sql);

    if ($exists) {
        echo "<p style='color: blue;'>ℹ️ Column already exists, skipping</p>";
        continue;
    }

    // Add the column
    $alter_sql = "ALTER TABLE {$fix['table']} ADD COLUMN {$fix['column']} {$fix['definition']} {$fix['position']}";
    echo "<p>Running: <code>$alter_sql</code></p>";

    $result = $wpdb->query($alter_sql);

    if ($result !== false) {
        echo "<p style='color: green;'>✅ Column added successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $wpdb->last_error . "</p>";

        // Try alternative without position
        $alt_sql = "ALTER TABLE {$fix['table']} ADD COLUMN {$fix['column']} {$fix['definition']}";
        echo "<p>Trying alternative: <code>$alt_sql</code></p>";

        $alt_result = $wpdb->query($alt_sql);
        if ($alt_result !== false) {
            echo "<p style='color: green;'>✅ Column added with alternative method</p>";
        } else {
            echo "<p style='color: red;'>❌ Alternative failed: " . $wpdb->last_error . "</p>";
        }
    }

    echo "<hr>";
}

echo "<h2>Post-Fix Column Status</h2>";
foreach ($column_checks as $table => $columns) {
    $result = $wpdb->get_results("DESCRIBE $table");
    if ($result) {
        $existing_fields = array_column($result, 'Field');
        foreach ($columns as $column) {
            $exists = in_array($column, $existing_fields);
            $status = $exists ? "✅ EXISTS" : "❌ STILL MISSING";
            echo "<p>$table.$column: $status</p>";
        }
    }
}

echo "<h2>Next Steps</h2>";
echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import') . "'>Test GEDCOM Import</a></p>";
?>