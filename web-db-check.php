<?php
/**
 * Web-based Database Check for HeritagePress
 * Access via browser to check database schema
 */

// Load WordPress
require_once('../../../wp-config.php');

// Security check - only allow admins
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

global $wpdb;

?>
<!DOCTYPE html>
<html>

<head>
    <title>HeritagePress Database Schema Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .warning {
            color: orange;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .status-ok {
            background-color: #d4edda;
        }

        .status-missing {
            background-color: #f8d7da;
        }
    </style>
</head>

<body>

    <h1>HeritagePress Database Schema Check</h1>

    <?php
    echo "<h2>Database Information</h2>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Prefix:</strong> " . $wpdb->prefix . "</p>";

    // Check what HeritagePress tables exist
    $existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
    echo "<p><strong>HeritagePress Tables Found:</strong> " . count($existing_tables) . "</p>";

    if (count($existing_tables) == 0) {
        echo "<div class='error'>";
        echo "<h3>❌ No HeritagePress Tables Found</h3>";
        echo "<p>The database schema has not been installed. This explains why the import is failing.</p>";
        echo "</div>";
    } else {
        echo "<h2>Existing Tables</h2>";
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Row Count</th><th>Size (MB)</th></tr>";

        foreach ($existing_tables as $table) {
            $table_short = str_replace($wpdb->prefix, '', $table);
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `$table`");

            // Get table size
            $size_result = $wpdb->get_row($wpdb->prepare(
                "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb' 
             FROM information_schema.TABLES 
             WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            $size_mb = $size_result ? $size_result->size_mb : 0;

            echo "<tr>";
            echo "<td>$table_short</td>";
            echo "<td>" . number_format($row_count) . "</td>";
            echo "<td>$size_mb</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Check for specific required tables for GEDCOM import
    $required_tables = [
        'hp_trees' => ['id', 'title', 'description'],
        'hp_people' => ['id', 'person_id', 'gedcom', 'tree_id'],
        'hp_families' => ['id', 'family_id', 'gedcom', 'tree_id'],
        'hp_sources' => ['id', 'source_id', 'gedcom', 'tree_id'],
        'hp_media' => ['id', 'media_id', 'gedcom', 'tree_id'],
        'hp_repositories' => ['id', 'name', 'gedcom', 'tree_id']
    ];

    echo "<h2>GEDCOM Import Table Requirements</h2>";
    echo "<table>";
    echo "<tr><th>Table</th><th>Status</th><th>Missing Columns</th></tr>";

    foreach ($required_tables as $table => $required_columns) {
        $full_table = $wpdb->prefix . $table;
        $table_exists = in_array($full_table, $existing_tables);

        if ($table_exists) {
            // Check columns
            $columns = $wpdb->get_results("DESCRIBE `$full_table`");
            $existing_columns = array_map(function ($col) {
                return $col->Field; }, $columns);
            $missing_columns = array_diff($required_columns, $existing_columns);

            if (empty($missing_columns)) {
                echo "<tr class='status-ok'>";
                echo "<td>$table</td>";
                echo "<td class='success'>✅ OK</td>";
                echo "<td>-</td>";
            } else {
                echo "<tr class='status-missing'>";
                echo "<td>$table</td>";
                echo "<td class='warning'>⚠️ Missing columns</td>";
                echo "<td>" . implode(', ', $missing_columns) . "</td>";
            }
        } else {
            echo "<tr class='status-missing'>";
            echo "<td>$table</td>";
            echo "<td class='error'>❌ Table missing</td>";
            echo "<td>N/A</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    // Check if we can fix missing columns
    $has_missing_columns = false;
    foreach ($required_tables as $table => $required_columns) {
        $full_table = $wpdb->prefix . $table;
        if (in_array($full_table, $existing_tables)) {
            $columns = $wpdb->get_results("DESCRIBE `$full_table`");
            $existing_columns = array_map(function ($col) {
                return $col->Field; }, $columns);
            $missing_columns = array_diff($required_columns, $existing_columns);
            if (!empty($missing_columns)) {
                $has_missing_columns = true;
                break;
            }
        }
    }

    if ($has_missing_columns || count($existing_tables) == 0) {
        echo "<h2>🔧 Database Fix Required</h2>";
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px;'>";

        if (count($existing_tables) == 0) {
            echo "<p><strong>Action Needed:</strong> Install the complete database schema</p>";
            echo "<p>The HeritagePress database tables have not been created. You need to run the schema installation.</p>";
        } else {
            echo "<p><strong>Action Needed:</strong> Fix missing columns</p>";
            echo "<p>Some tables exist but are missing required columns for GEDCOM import.</p>";
        }

        echo "<p><strong>Solution:</strong> Click the button below to automatically fix the database schema:</p>";
        echo "<button onclick='fixDatabase()' style='background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Fix Database Schema</button>";
        echo "</div>";

        echo "<script>";
        echo "function fixDatabase() {";
        echo "  if (confirm('This will create missing tables and add missing columns. Continue?')) {";
        echo "    window.location.href = '" . admin_url('admin.php?page=heritagepress-import&action=fix_schema') . "';";
        echo "  }";
        echo "}";
        echo "</script>";
    } else {
        echo "<h2>✅ Database Schema Status</h2>";
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px;'>";
        echo "<p><strong>✅ All Good!</strong> Database schema is properly installed.</p>";
        echo "<p>All required tables and columns are present for GEDCOM import.</p>";
        echo "</div>";
    }

    ?>

    <hr>
    <p><a href="<?php echo admin_url('admin.php?page=heritagepress-import'); ?>">← Back to Import Page</a></p>

</body>

</html>