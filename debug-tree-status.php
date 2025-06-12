<?php
// Debug script to check tree database status and template variables
require_once('../../../wp-config.php');

echo "<h2>Tree Dropdown Debug</h2>";

global $wpdb;

// 1. Check if tables exist
echo "<h3>1. Database Tables Check</h3>";
$table_name = $wpdb->prefix . 'hp_trees';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if ($table_exists) {
    echo "<p>✓ Table $table_name exists</p>";
} else {
    echo "<p>✗ Table $table_name does NOT exist</p>";
    exit;
}

// 2. Check table structure
echo "<h3>2. Table Structure</h3>";
$columns = $wpdb->get_results("DESCRIBE $table_name");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Key</th><th>Default</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>{$column->Field}</td>";
    echo "<td>{$column->Type}</td>";
    echo "<td>{$column->Key}</td>";
    echo "<td>{$column->Default}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Check raw data
echo "<h3>3. Raw Tree Data</h3>";
$trees_raw = $wpdb->get_results("SELECT * FROM $table_name ORDER BY title ASC");
echo "<p>Trees found: " . count($trees_raw) . "</p>";

if (!empty($trees_raw)) {
    echo "<table border='1'>";
    echo "<tr>";
    foreach (get_object_vars($trees_raw[0]) as $key => $value) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    
    foreach ($trees_raw as $tree) {
        echo "<tr>";
        foreach (get_object_vars($tree) as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No trees found in database!</p>";
}

// 4. Test the exact query used in step1-upload.php
echo "<h3>4. Template Query Test</h3>";
$trees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_trees ORDER BY title ASC");
echo "<p>Query result count: " . count($trees) . "</p>";

if (!empty($trees)) {
    echo "<h4>Dropdown HTML Preview:</h4>";
    echo "<select>";
    echo '<option value="new">Create New Tree</option>';
    foreach ($trees as $tree) {
        if (isset($tree->id)) {
            echo '<option value="' . esc_attr($tree->id) . '">' . esc_html($tree->title) . '</option>';
        } else {
            echo "<!-- Tree missing ID: " . print_r(get_object_vars($tree), true) . " -->";
        }
    }
    echo "</select>";
}

// 5. Test ImportExportManager
echo "<h3>5. ImportExportManager Test</h3>";
try {
    $manager = new HeritagePress\Admin\ImportExportManager();
    echo "<p>✓ ImportExportManager created</p>";
    
    // Use reflection to access the private get_trees method
    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('get_trees');
    $method->setAccessible(true);
    $manager_trees = $method->invoke($manager);
    
    echo "<p>Trees from ImportExportManager: " . count($manager_trees) . "</p>";
    
    if (!empty($manager_trees)) {
        echo "<ul>";
        foreach ($manager_trees as $tree) {
            echo "<li>ID: {$tree->id}, Title: {$tree->title}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error testing ImportExportManager: " . $e->getMessage() . "</p>";
}

// 6. Check WordPress admin context
echo "<h3>6. WordPress Context</h3>";
echo "<p>is_admin(): " . (is_admin() ? 'YES' : 'NO') . "</p>";
echo "<p>current_user_can('manage_options'): " . (current_user_can('manage_options') ? 'YES' : 'NO') . "</p>";
echo "<p>WordPress loaded: " . (function_exists('wp_get_current_user') ? 'YES' : 'NO') . "</p>";

// 7. Check for any PHP errors
echo "<h3>7. Error Log Check</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $recent_errors = tail($error_log, 20);
    echo "<pre>" . htmlspecialchars($recent_errors) . "</pre>";
} else {
    echo "<p>No error log found or accessible</p>";
}

// Helper function to get last N lines of a file
function tail($file, $lines = 10) {
    $handle = fopen($file, "r");
    $linecounter = $lines;
    $pos = -2;
    $beginning = false;
    $text = array();
    
    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $linecounter--;
        if ($beginning) {
            rewind($handle);
        }
        $text[$lines - $linecounter - 1] = fgets($handle);
        if ($beginning) break;
    }
    fclose($handle);
    return array_reverse($text);
}
?>
