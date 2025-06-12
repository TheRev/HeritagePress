<?php
/**
 * Test Column Fix using Database Manager
 * This runs the official HeritagePress column fix method
 */

// Load WordPress
require_once('../../../wp-config.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

echo "<h1>HeritagePress Column Fix Test</h1>";

// Load the Database Manager
require_once __DIR__ . '/includes/Database/Manager.php';
require_once __DIR__ . '/includes/Database/WPHelper.php';

use HeritagePress\Database\Manager;

try {
    // Create database manager instance
    $db_manager = new Manager(__DIR__, '1.0.0');
    echo "<p style='color: green;'>✅ Database Manager loaded successfully</p>";

    // Trigger the column fixes
    echo "<h2>Running Column Fixes</h2>";
    $results = $db_manager->install_missing_columns();

    echo "<h3>Results:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Table</th><th>Column</th><th>Status</th></tr>";

    $total_fixes = 0;
    $successful_fixes = 0;

    foreach ($results as $table => $columns) {
        if (isset($columns['error'])) {
            echo "<tr style='background-color: #f8d7da;'>";
            echo "<td>$table</td>";
            echo "<td colspan='2'>ERROR: " . $columns['error'] . "</td>";
            echo "</tr>";
        } else {
            foreach ($columns as $column => $status) {
                $total_fixes++;
                $bg_color = '';
                $status_icon = '';

                if ($status === 'added') {
                    $bg_color = '#d4edda';
                    $status_icon = '✅ ';
                    $successful_fixes++;
                } elseif ($status === 'exists') {
                    $bg_color = '#fff3cd';
                    $status_icon = '⚠️ ';
                    $successful_fixes++;
                } else {
                    $bg_color = '#f8d7da';
                    $status_icon = '❌ ';
                }

                echo "<tr style='background-color: $bg_color;'>";
                echo "<td>$table</td>";
                echo "<td>$column</td>";
                echo "<td>$status_icon$status</td>";
                echo "</tr>";
            }
        }
    }

    echo "</table>";

    echo "<h3>Summary</h3>";
    echo "<p><strong>Total columns processed:</strong> $total_fixes</p>";
    echo "<p><strong>Successful:</strong> $successful_fixes</p>";

    if ($successful_fixes === $total_fixes && $total_fixes > 0) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>🎉 All Columns Fixed!</h3>";
        echo "<p style='margin: 0; color: #155724;'>The database now has all required columns for GEDCOM import.</p>";
        echo "<p style='margin: 10px 0 0 0;'><a href='" . admin_url('admin.php?page=heritagepress-import') . "' style='color: #155724;'>Go to Import Page</a></p>";
        echo "</div>";
    } elseif ($total_fixes === 0) {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #856404; margin: 0 0 10px 0;'>⚠️ No Results</h3>";
        echo "<p style='margin: 0; color: #856404;'>The column fix process returned no results. This might indicate the tables don't exist yet.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #721c24; margin: 0 0 10px 0;'>❌ Some Fixes Failed</h3>";
        echo "<p style='margin: 0; color: #721c24;'>Please check the errors above.</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24;'>❌ Error</h3>";
    echo "<p style='color: #721c24;'>Failed to run column fixes: " . $e->getMessage() . "</p>";
    echo "</div>";

    error_log('HeritagePress Column Fix Error: ' . $e->getMessage());
}

echo "<hr>";
echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import') . "'>← Go to Import Page</a> | ";
echo "<a href='" . admin_url() . "'>WordPress Admin</a></p>";
?>