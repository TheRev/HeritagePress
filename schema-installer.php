<?php
/**
 * HeritagePress Schema Installer - Web Interface
 * This installs the complete database schema for HeritagePress
 */

// Load WordPress
require_once('../../../wp-config.php');

// Security check - only allow admins
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

global $wpdb;

// Check if we should run the installation
$install_now = isset($_GET['install']) && $_GET['install'] === 'now';

?>
<!DOCTYPE html>
<html>
<head>
    <title>HeritagePress Schema Installer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .box { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success-box { background: #d4edda; border: 1px solid #c3e6cb; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; }
        .warning-box { background: #fff3cd; border: 1px solid #ffeaa7; }
        .info-box { background: #d1ecf1; border: 1px solid #bee5eb; }
        .install-btn { background: #007cba; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .install-btn:hover { background: #005a87; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<h1>HeritagePress Database Schema Installer</h1>

<?php

if ($install_now) {
    echo "<h2>🔧 Installing Database Schema...</h2>";
    
    try {
        // Include required files
        require_once(dirname(__FILE__) . '/includes/Database/Manager.php');
        require_once(dirname(__FILE__) . '/includes/Helpers/WPHelper.php');
        
        use HeritagePress\Database\Manager;
        
        // Get current table count
        $before_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
        $before_count = count($before_tables);
        
        echo "<p class='info'>Tables before installation: $before_count</p>";
        
        // Create database manager
        $plugin_dir = dirname(__FILE__) . '/';
        $manager = new Manager($plugin_dir, '1.0.0');
        
        echo "<p class='success'>✅ Database Manager created successfully</p>";
        
        // Run installation
        $manager->install();
        
        echo "<p class='success'>✅ Schema installation completed</p>";
        
        // Get final table count
        $after_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
        $after_count = count($after_tables);
        $added_count = $after_count - $before_count;
        
        echo "<div class='success-box'>";
        echo "<h3>🎉 Installation Complete!</h3>";
        echo "<p><strong>Tables before:</strong> $before_count</p>";
        echo "<p><strong>Tables after:</strong> $after_count</p>";
        echo "<p><strong>Tables added:</strong> $added_count</p>";
        echo "</div>";
        
        if ($after_count >= 35) {
            echo "<div class='success-box'>";
            echo "<h3>✅ Success!</h3>";
            echo "<p>HeritagePress database schema has been successfully installed.</p>";
            echo "<p>You can now import GEDCOM files.</p>";
            echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import') . "'>Go to Import Page</a></p>";
            echo "</div>";
        } else {
            echo "<div class='warning-box'>";
            echo "<h3>⚠️ Partial Installation</h3>";
            echo "<p>Some tables may not have been created. Expected 35+ tables, got $after_count.</p>";
            echo "</div>";
        }
        
        // Show installed tables
        echo "<h3>Installed Tables</h3>";
        echo "<div style='max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>";
        foreach ($after_tables as $table) {
            $clean_name = str_replace($wpdb->prefix, '', $table);
            echo "<p>• $clean_name</p>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error-box'>";
        echo "<h3>❌ Installation Failed</h3>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "</div>";
    }
    
} else {
    // Show current status and installation option
    echo "<h2>Current Database Status</h2>";
    
    $existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
    $table_count = count($existing_tables);
    
    echo "<div class='info-box'>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Prefix:</strong> " . $wpdb->prefix . "</p>";
    echo "<p><strong>HeritagePress Tables:</strong> $table_count</p>";
    echo "</div>";
    
    if ($table_count == 0) {
        echo "<div class='warning-box'>";
        echo "<h3>⚠️ No HeritagePress Tables Found</h3>";
        echo "<p>The database schema has not been installed. This is why GEDCOM import is failing.</p>";
        echo "<p>Click the button below to install the complete database schema.</p>";
        echo "</div>";
        
        echo "<div class='info-box'>";
        echo "<h3>What will be installed:</h3>";
        echo "<ul>";
        echo "<li><strong>Core Tables (22):</strong> Trees, People, Families, Events, Sources, etc.</li>";
        echo "<li><strong>Advanced Tables (17):</strong> Albums, DNA, Reports, Templates, etc.</li>";
        echo "<li><strong>GEDCOM 7 Support:</strong> Modern GEDCOM standards</li>";
        echo "<li><strong>Default Data:</strong> Event types, countries, media types</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<p><button class='install-btn' onclick='installSchema()'>Install Database Schema</button></p>";
        
        echo "<script>";
        echo "function installSchema() {";
        echo "  if (confirm('This will create the HeritagePress database tables. Continue?')) {";
        echo "    window.location.href = '?install=now';";
        echo "  }";
        echo "}";
        echo "</script>";
        
    } else {
        echo "<div class='success-box'>";
        echo "<h3>✅ HeritagePress Tables Found</h3>";
        echo "<p>Found $table_count HeritagePress tables in the database.</p>";
        echo "</div>";
        
        // Check for required GEDCOM import tables
        $required_tables = [
            'hp_trees' => 'Family tree definitions',
            'hp_people' => 'Individual person records',
            'hp_families' => 'Family relationship records',
            'hp_sources' => 'Source citations',
            'hp_media' => 'Media files and attachments',
            'hp_repositories' => 'Source repositories'
        ];
        
        echo "<h3>Required Tables Status</h3>";
        echo "<table>";
        echo "<tr><th>Table</th><th>Status</th><th>Purpose</th></tr>";
        
        $missing_required = 0;
        foreach ($required_tables as $table => $purpose) {
            $full_table = $wpdb->prefix . $table;
            $exists = in_array($full_table, $existing_tables);
            $status = $exists ? '<span class="success">✅ Present</span>' : '<span class="error">❌ Missing</span>';
            if (!$exists) $missing_required++;
            
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>$status</td>";
            echo "<td>$purpose</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($missing_required > 0) {
            echo "<div class='warning-box'>";
            echo "<h3>⚠️ Missing Required Tables</h3>";
            echo "<p>Some tables required for GEDCOM import are missing.</p>";
            echo "<p><button class='install-btn' onclick='installSchema()'>Install Missing Tables</button></p>";
            echo "</div>";
            
            echo "<script>";
            echo "function installSchema() {";
            echo "  if (confirm('This will install missing HeritagePress database tables. Continue?')) {";
            echo "    window.location.href = '?install=now';";
            echo "  }";
            echo "}";
            echo "</script>";
        } else {
            echo "<div class='success-box'>";
            echo "<h3>✅ Schema Complete</h3>";
            echo "<p>All required tables are present. GEDCOM import should work.</p>";
            echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import') . "'>Go to Import Page</a></p>";
            echo "</div>";
        }
        
        // Show existing tables
        if ($table_count < 20) {
            echo "<h3>Existing Tables</h3>";
            echo "<ul>";
            foreach ($existing_tables as $table) {
                $clean_name = str_replace($wpdb->prefix, '', $table);
                echo "<li>$clean_name</li>";
            }
            echo "</ul>";
        }
    }
}

?>

<hr>
<p><a href="<?php echo admin_url('admin.php?page=heritagepress-import'); ?>">← Back to Import Page</a> | <a href="<?php echo admin_url(); ?>">WordPress Admin</a></p>

</body>
</html>
