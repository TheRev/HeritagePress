<?php
/**
 * Simple Column Fixer for HeritagePress
 * Add missing columns that the GEDCOM import needs
 */

// Load WordPress
require_once('../../../wp-config.php');

// Security check - only allow admins
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

global $wpdb;

// Check if we should run the fix
$fix_now = isset($_GET['fix']) && $_GET['fix'] === 'now';

?>
<!DOCTYPE html>
<html>

<head>
    <title>HeritagePress Column Fixer</title>
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

        .box {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }

        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
        }

        .fix-btn {
            background: #007cba;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
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
    </style>
</head>

<body>

    <h1>HeritagePress Column Fixer</h1>

    <?php

    if ($fix_now) {
        echo "<h2>🔧 Adding Missing Columns...</h2>";

        // Define the missing columns that need to be added
        $column_fixes = [
            'wp_hp_people' => [
                'person_id' => "VARCHAR(50) NOT NULL AFTER gedcom"
            ],
            'wp_hp_families' => [
                'family_id' => "VARCHAR(50) NOT NULL AFTER gedcom"
            ],
            'wp_hp_sources' => [
                'source_id' => "VARCHAR(50) NOT NULL AFTER gedcom"
            ],
            'wp_hp_repositories' => [
                'name' => "VARCHAR(255) NOT NULL AFTER repo_id"
            ],
            'wp_hp_media' => [
                'media_id' => "VARCHAR(50) NOT NULL AFTER gedcom"
            ]
        ];

        $total_fixes = 0;
        $successful_fixes = 0;

        foreach ($column_fixes as $table => $columns) {
            echo "<h3>Fixing Table: $table</h3>";

            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$table_exists) {
                echo "<p class='error'>❌ Table $table does not exist, skipping</p>";
                continue;
            }

            foreach ($columns as $column_name => $column_definition) {
                $total_fixes++;

                // Check if column already exists
                $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table LIKE '$column_name'");

                if ($column_exists) {
                    echo "<p class='success'>✅ Column $column_name already exists in $table</p>";
                    $successful_fixes++;
                } else {
                    // Add the missing column
                    $sql = "ALTER TABLE $table ADD COLUMN $column_name $column_definition";
                    echo "<p>Adding column: <code>$sql</code></p>";

                    $result = $wpdb->query($sql);
                    if ($result !== false) {
                        echo "<p class='success'>✅ Successfully added column $column_name to $table</p>";
                        $successful_fixes++;
                    } else {
                        echo "<p class='error'>❌ Failed to add column $column_name to $table: " . $wpdb->last_error . "</p>";
                    }
                }
            }
        }

        echo "<div class='success-box'>";
        echo "<h3>🎉 Column Fix Complete!</h3>";
        echo "<p><strong>Total fixes attempted:</strong> $total_fixes</p>";
        echo "<p><strong>Successful:</strong> $successful_fixes</p>";
        echo "</div>";

        if ($successful_fixes === $total_fixes) {
            echo "<div class='success-box'>";
            echo "<h3>✅ All Columns Fixed!</h3>";
            echo "<p>GEDCOM import should now work without database errors.</p>";
            echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import') . "'>Test Import Again</a></p>";
            echo "</div>";
        }

    } else {
        // Show current status and fix option
        echo "<h2>Missing Column Check</h2>";

        // Check which columns are missing
        $required_columns = [
            'wp_hp_people' => ['person_id'],
            'wp_hp_families' => ['family_id'],
            'wp_hp_sources' => ['source_id'],
            'wp_hp_repositories' => ['name'],
            'wp_hp_media' => ['media_id']
        ];

        $missing_columns = [];
        $total_missing = 0;

        echo "<table>";
        echo "<tr><th>Table</th><th>Required Column</th><th>Status</th></tr>";

        foreach ($required_columns as $table => $columns) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

            if (!$table_exists) {
                echo "<tr><td>$table</td><td>-</td><td class='error'>❌ Table Missing</td></tr>";
                continue;
            }

            foreach ($columns as $column) {
                $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table LIKE '$column'");

                if ($column_exists) {
                    echo "<tr><td>$table</td><td>$column</td><td class='success'>✅ Present</td></tr>";
                } else {
                    echo "<tr><td>$table</td><td>$column</td><td class='error'>❌ Missing</td></tr>";
                    $missing_columns[] = "$table.$column";
                    $total_missing++;
                }
            }
        }
        echo "</table>";

        if ($total_missing > 0) {
            echo "<div class='warning-box'>";
            echo "<h3>⚠️ Missing Columns Found</h3>";
            echo "<p>Found $total_missing missing columns required for GEDCOM import:</p>";
            echo "<ul>";
            foreach ($missing_columns as $column) {
                echo "<li>$column</li>";
            }
            echo "</ul>";
            echo "<p>These columns are needed for the import process to work correctly.</p>";
            echo "<p><button class='fix-btn' onclick='fixColumns()'>Add Missing Columns</button></p>";
            echo "</div>";

            echo "<script>";
            echo "function fixColumns() {";
            echo "  if (confirm('This will add the missing columns to your database tables. Continue?')) {";
            echo "    window.location.href = '?fix=now';";
            echo "  }";
            echo "}";
            echo "</script>";

        } else {
            echo "<div class='success-box'>";
            echo "<h3>✅ All Columns Present</h3>";
            echo "<p>All required columns are present. GEDCOM import should work correctly.</p>";
            echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import') . "'>Go to Import Page</a></p>";
            echo "</div>";
        }
    }

    ?>

    <hr>
    <p><a href="<?php echo admin_url('admin.php?page=heritagepress-import'); ?>">← Back to Import Page</a> | <a
            href="<?php echo admin_url(); ?>">WordPress Admin</a></p>

</body>

</html>