<?php
/**
 * Manual Column Addition Script - One-time execution
 * 
 * This script adds the missing columns that are causing GEDCOM import failures.
 * Run this once via web browser, then delete this file.
 */

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Manual Column Addition Script</h1>";

// Database connection parameters for MAMP
$host = 'localhost';
$dbname = 'wordpress';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p style='color: green;'>✅ Database connected successfully</p>";

    // Define the columns to add
    $column_fixes = [
        [
            'table' => 'wp_hp_people',
            'column' => 'person_id',
            'definition' => 'VARCHAR(50) NOT NULL',
            'position' => 'AFTER gedcom'
        ],
        [
            'table' => 'wp_hp_families',
            'column' => 'family_id',
            'definition' => 'VARCHAR(50) NOT NULL',
            'position' => 'AFTER gedcom'
        ],
        [
            'table' => 'wp_hp_sources',
            'column' => 'source_id',
            'definition' => 'VARCHAR(50) NOT NULL',
            'position' => 'AFTER gedcom'
        ],
        [
            'table' => 'wp_hp_repositories',
            'column' => 'name',
            'definition' => 'VARCHAR(255) NOT NULL',
            'position' => 'AFTER repo_id'
        ],
        [
            'table' => 'wp_hp_media',
            'column' => 'media_id',
            'definition' => 'VARCHAR(50) NOT NULL',
            'position' => 'AFTER gedcom'
        ]
    ];

    echo "<h2>Adding Missing Columns</h2>";

    $success_count = 0;
    $total_count = count($column_fixes);

    foreach ($column_fixes as $fix) {
        $table = $fix['table'];
        $column = $fix['column'];
        $definition = $fix['definition'];
        $position = $fix['position'];

        echo "<h3>Table: {$table}</h3>";

        // Check if table exists
        $table_check = $pdo->query("SHOW TABLES LIKE '{$table}'")->fetchColumn();
        if (!$table_check) {
            echo "<p style='color: red;'>❌ Table {$table} does not exist</p>";
            continue;
        }

        // Check if column already exists
        $column_check = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'")->fetchColumn();
        if ($column_check) {
            echo "<p style='color: orange;'>⚠️ Column {$column} already exists in {$table}</p>";
            $success_count++;
            continue;
        }

        // Add the column
        $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$definition} {$position}";
        echo "<p>Executing: <code>{$sql}</code></p>";

        try {
            $pdo->exec($sql);
            echo "<p style='color: green;'>✅ Successfully added column {$column} to {$table}</p>";
            $success_count++;
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error adding column {$column} to {$table}: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h2>Summary</h2>";
    echo "<p><strong>Total columns processed:</strong> {$total_count}</p>";
    echo "<p><strong>Successfully added/verified:</strong> {$success_count}</p>";

    if ($success_count === $total_count) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>🎉 All Columns Added Successfully!</h3>";
        echo "<p style='margin: 0; color: #155724;'>The database now has all required columns for GEDCOM import.</p>";
        echo "<p style='margin: 10px 0 0 0;'><strong>Next steps:</strong></p>";
        echo "<ol style='margin: 5px 0 0 20px; color: #155724;'>";
        echo "<li>Go to the <a href='http://localhost:8888/wordpress/wp-admin/admin.php?page=heritagepress-import' style='color: #155724;'>HeritagePress Import Page</a></li>";
        echo "<li>Try uploading your GEDCOM file again</li>";
        echo "<li>The import should now proceed to completion without database errors</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #856404; margin: 0 0 10px 0;'>⚠️ Some Columns Could Not Be Added</h3>";
        echo "<p style='margin: 0; color: #856404;'>Please check the errors above and try to resolve them manually.</p>";
        echo "</div>";
    }

    // Verify the fixes by showing current table structures
    echo "<h2>Verification - Current Table Structures</h2>";
    foreach ($column_fixes as $fix) {
        $table = $fix['table'];
        echo "<h4>{$table}</h4>";

        try {
            $columns = $pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                $highlight = ($col['Field'] === $fix['column']) ? "style='background-color: #d4edda;'" : "";
                echo "<tr {$highlight}>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Error showing columns for {$table}: " . $e->getMessage() . "</p>";
        }
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check that:</p>";
    echo "<ul>";
    echo "<li>MAMP is running</li>";
    echo "<li>MySQL service is started</li>";
    echo "<li>Database name 'wordpress' exists</li>";
    echo "<li>Username 'root' and password 'root' are correct</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>⚠️ Important:</strong> Delete this file after successful execution for security.</p>";
echo "<p><a href='http://localhost:8888/wordpress/wp-admin/admin.php?page=heritagepress-import'>← Go to HeritagePress Import</a></p>";
?>