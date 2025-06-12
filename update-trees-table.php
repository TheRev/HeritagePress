<?php
/**
 * Database Update Script - Add TNG-style fields to Trees table
 * Adds owner contact information and privacy fields to match TNG admin_newtree.php
 */

require_once('../../../wp-config.php');

echo "<h1>🔧 HeritagePress Database Update - Trees Enhancement</h1>";
echo "<p>Adding TNG-style owner contact and privacy fields to trees table...</p>";

global $wpdb;
$table_name = $wpdb->prefix . 'hp_trees';

try {
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

    if (!$table_exists) {
        echo "<p style='color: red;'>❌ Trees table does not exist. Please run the main schema installation first.</p>";
        exit;
    }

    echo "<h2>Current Table Structure</h2>";
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    $existing_columns = [];

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column->Field}</td>";
        echo "<td>{$column->Type}</td>";
        echo "<td>{$column->Null}</td>";
        echo "<td>{$column->Key}</td>";
        echo "<td>{$column->Default}</td>";
        echo "</tr>";
        $existing_columns[] = $column->Field;
    }
    echo "</table>";

    echo "<h2>Adding Missing TNG-style Columns</h2>";

    // Define new columns to add
    $new_columns = [
        'owner' => "ADD COLUMN owner varchar(255) DEFAULT NULL AFTER description",
        'email' => "ADD COLUMN email varchar(255) DEFAULT NULL AFTER owner",
        'address' => "ADD COLUMN address varchar(255) DEFAULT NULL AFTER email",
        'city' => "ADD COLUMN city varchar(100) DEFAULT NULL AFTER address",
        'state' => "ADD COLUMN state varchar(100) DEFAULT NULL AFTER city",
        'zip' => "ADD COLUMN zip varchar(20) DEFAULT NULL AFTER state",
        'country' => "ADD COLUMN country varchar(100) DEFAULT NULL AFTER zip",
        'phone' => "ADD COLUMN phone varchar(50) DEFAULT NULL AFTER country",
        'private' => "ADD COLUMN private tinyint(1) DEFAULT 0 AFTER privacy_level",
        'disallowgedcreate' => "ADD COLUMN disallowgedcreate tinyint(1) DEFAULT 0 AFTER private",
        'disallowpdf' => "ADD COLUMN disallowpdf tinyint(1) DEFAULT 0 AFTER disallowgedcreate"
    ];

    $added_count = 0;
    $skipped_count = 0;

    foreach ($new_columns as $column_name => $alter_sql) {
        if (!in_array($column_name, $existing_columns)) {
            $sql = "ALTER TABLE $table_name $alter_sql";

            echo "<p>Adding column '$column_name'...</p>";
            echo "<code>$sql</code><br>";

            $result = $wpdb->query($sql);

            if ($result !== false) {
                echo "<p style='color: green;'>✅ Successfully added '$column_name'</p>";
                $added_count++;
            } else {
                echo "<p style='color: red;'>❌ Failed to add '$column_name': " . $wpdb->last_error . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>⏭️ Column '$column_name' already exists, skipping</p>";
            $skipped_count++;
        }
    }

    echo "<h2>Update Summary</h2>";
    echo "<p>✅ <strong>$added_count</strong> columns added</p>";
    echo "<p>⏭️ <strong>$skipped_count</strong> columns skipped (already exist)</p>";

    if ($added_count > 0) {
        echo "<h2>Updated Table Structure</h2>";
        $updated_columns = $wpdb->get_results("DESCRIBE $table_name");

        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

        foreach ($updated_columns as $column) {
            $is_new = !in_array($column->Field, $existing_columns);
            $style = $is_new ? "background-color: #d4edda;" : "";

            echo "<tr style='$style'>";
            echo "<td>{$column->Field}" . ($is_new ? " <strong>(NEW)</strong>" : "") . "</td>";
            echo "<td>{$column->Type}</td>";
            echo "<td>{$column->Null}</td>";
            echo "<td>{$column->Key}</td>";
            echo "<td>{$column->Default}</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "<h2>🎉 Database Update Complete!</h2>";
        echo "<p>Your trees table now includes all TNG-style fields:</p>";
        echo "<ul>";
        echo "<li><strong>Owner Contact:</strong> owner, email, address, city, state, zip, country, phone</li>";
        echo "<li><strong>Privacy Options:</strong> private, disallowgedcreate, disallowpdf</li>";
        echo "</ul>";

        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ul>";
        echo "<li>Visit HeritagePress → Trees in WordPress admin</li>";
        echo "<li>Edit existing trees to add owner contact information</li>";
        echo "<li>Configure privacy settings as needed</li>";
        echo "</ul>";
    } else {
        echo "<p>No database changes were needed. All columns already exist!</p>";
    }

} catch (Exception $e) {
    echo "<h2>❌ Database Update Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>