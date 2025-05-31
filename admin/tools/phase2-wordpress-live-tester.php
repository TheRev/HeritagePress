<?php
/**
 * Heritage Press - Phase 2 WordPress Live Testing Tool
 * Tests the plugin in a live WordPress environment (XAMPP)
 * 
 * @package HeritagePress
 * @version 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class HeritagePress_Phase2_LiveTester {
    
    private $results = [];
    private $errors = [];
    private $warnings = [];
    
    public function __construct() {
        $this->results = [];
        $this->errors = [];
        $this->warnings = [];
    }
    
    /**
     * Run comprehensive Phase 2 WordPress tests
     */
    public function run_all_tests() {
        $this->log_header("Heritage Press - Phase 2 WordPress Live Testing");
        
        // Test Categories
        $this->test_wordpress_environment();
        $this->test_plugin_activation();
        $this->test_database_integration();
        $this->test_admin_interface();
        $this->test_ajax_endpoints();
        $this->test_public_interface();
        $this->test_gedcom_functionality();
        $this->test_performance_metrics();
        
        $this->display_results();
        $this->generate_phase2_recommendations();
    }
    
    /**
     * Test WordPress environment compatibility
     */
    private function test_wordpress_environment() {
        $this->log_section("WordPress Environment Testing");
        
        // WordPress version
        global $wp_version;
        $this->log_test("WordPress Version", $wp_version, $wp_version >= '6.0');
        
        // PHP version
        $php_version = PHP_VERSION;
        $this->log_test("PHP Version", $php_version, version_compare($php_version, '7.4', '>='));
        
        // MySQL version
        global $wpdb;
        $mysql_version = $wpdb->get_var("SELECT VERSION()");
        $this->log_test("MySQL Version", $mysql_version, version_compare($mysql_version, '5.7', '>='));
        
        // Memory limit
        $memory_limit = ini_get('memory_limit');
        $this->log_test("PHP Memory Limit", $memory_limit, true);
        
        // Plugin directory permissions
        $plugin_dir = WP_PLUGIN_DIR . '/heritage-press';
        $writable = is_writable($plugin_dir);
        $this->log_test("Plugin Directory Writable", $writable ? 'Yes' : 'No', $writable);
        
        // WordPress debug mode
        $debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->log_test("WordPress Debug Mode", $debug_mode ? 'Enabled' : 'Disabled', true);
    }
    
    /**
     * Test plugin activation status
     */
    private function test_plugin_activation() {
        $this->log_section("Plugin Activation Testing");
        
        // Check if plugin is active
        $plugin_file = 'heritage-press/heritage-press.php';
        $is_active = is_plugin_active($plugin_file);
        $this->log_test("Plugin Active", $is_active ? 'Yes' : 'No', $is_active);
        
        // Check if main class exists
        $main_class_exists = class_exists('HeritagePress');
        $this->log_test("Main Plugin Class", $main_class_exists ? 'Loaded' : 'Missing', $main_class_exists);
        
        // Check autoloader
        $autoloader_exists = class_exists('HeritagePress\\Core\\Autoloader');
        $this->log_test("Autoloader", $autoloader_exists ? 'Loaded' : 'Missing', $autoloader_exists);
        
        // Check service container
        $container_exists = class_exists('HeritagePress\\Core\\ServiceContainer');
        $this->log_test("Service Container", $container_exists ? 'Loaded' : 'Missing', $container_exists);
    }
    
    /**
     * Test database integration
     */
    private function test_database_integration() {
        $this->log_section("Database Integration Testing");
        
        global $wpdb;
        
        // Test core tables existence
        $tables = [
            'heritage_individuals',
            'heritage_families', 
            'heritage_sources',
            'heritage_citations',
            'heritage_places',
            'heritage_events'
        ];
        
        foreach ($tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
            $this->log_test("Table: $table", $exists ? 'Exists' : 'Missing', $exists);
        }
        
        // Test table record counts
        $this->test_table_record_count('heritage_individuals');
        $this->test_table_record_count('heritage_families');
        $this->test_table_record_count('heritage_sources');
    }
    
    /**
     * Test admin interface functionality
     */
    private function test_admin_interface() {
        $this->log_section("Admin Interface Testing");
        
        // Check admin menu registration
        global $admin_page_hooks;
        $menu_exists = isset($admin_page_hooks['heritage-press']);
        $this->log_test("Admin Menu Registered", $menu_exists ? 'Yes' : 'No', $menu_exists);
        
        // Check admin capabilities
        $user_can_manage = current_user_can('manage_heritage_press');
        $this->log_test("User Permissions", $user_can_manage ? 'Authorized' : 'No Access', true);
        
        // Check admin CSS/JS enqueuing
        $this->test_admin_assets();
    }
    
    /**
     * Test AJAX endpoints
     */
    private function test_ajax_endpoints() {
        $this->log_section("AJAX Endpoints Testing");
        
        $endpoints = [
            'heritage_search_individuals',
            'heritage_get_individual', 
            'heritage_save_individual',
            'heritage_delete_individual',
            'heritage_upload_gedcom',
            'heritage_get_dashboard_stats'
        ];
        
        foreach ($endpoints as $endpoint) {
            $action_exists = has_action("wp_ajax_$endpoint");
            $this->log_test("AJAX: $endpoint", $action_exists ? 'Registered' : 'Missing', $action_exists);
        }
    }
    
    /**
     * Test public interface
     */
    private function test_public_interface() {
        $this->log_section("Public Interface Testing");
        
        // Check public CSS/JS
        $this->test_public_assets();
        
        // Check shortcode registration
        $shortcode_exists = shortcode_exists('heritage_family_tree');
        $this->log_test("Family Tree Shortcode", $shortcode_exists ? 'Registered' : 'Missing', $shortcode_exists);
        
        // Check public query vars
        global $wp;
        $query_vars = $wp->public_query_vars;
        $this->log_test("Public Query Vars", count($query_vars) . ' registered', true);
    }
    
    /**
     * Test GEDCOM functionality
     */
    private function test_gedcom_functionality() {
        $this->log_section("GEDCOM Functionality Testing");
        
        // Check GEDCOM parser class
        $parser_exists = class_exists('HeritagePress\\GEDCOM\\Parser');
        $this->log_test("GEDCOM Parser", $parser_exists ? 'Available' : 'Missing', $parser_exists);
        
        // Check upload directory
        $upload_dir = wp_upload_dir();
        $gedcom_dir = $upload_dir['basedir'] . '/heritage-press/gedcom';
        $dir_exists = is_dir($gedcom_dir);
        $this->log_test("GEDCOM Upload Directory", $dir_exists ? 'Exists' : 'Missing', $dir_exists);
        
        // Check file permissions
        if ($dir_exists) {
            $writable = is_writable($gedcom_dir);
            $this->log_test("GEDCOM Directory Writable", $writable ? 'Yes' : 'No', $writable);
        }
    }
    
    /**
     * Test performance metrics
     */
    private function test_performance_metrics() {
        $this->log_section("Performance Metrics Testing");
        
        // Memory usage
        $memory_usage = memory_get_usage(true);
        $memory_mb = round($memory_usage / 1024 / 1024, 2);
        $this->log_test("Current Memory Usage", $memory_mb . " MB", $memory_mb < 64);
        
        // Database query time
        $start_time = microtime(true);
        global $wpdb;
        $wpdb->get_results("SELECT COUNT(*) FROM {$wpdb->prefix}heritage_individuals");
        $query_time = round((microtime(true) - $start_time) * 1000, 2);
        $this->log_test("DB Query Time", $query_time . " ms", $query_time < 100);
        
        // Plugin load time estimation
        $this->estimate_plugin_load_time();
    }
    
    /**
     * Helper methods
     */
    private function test_table_record_count($table) {
        global $wpdb;
        $full_table_name = $wpdb->prefix . $table;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
        $this->log_test("Records in $table", $count !== null ? $count : 'Error', true);
    }
    
    private function test_admin_assets() {
        global $wp_styles, $wp_scripts;
        
        $admin_css_exists = isset($wp_styles->registered['heritage-press-admin']);
        $this->log_test("Admin CSS", $admin_css_exists ? 'Registered' : 'Missing', $admin_css_exists);
        
        $admin_js_exists = isset($wp_scripts->registered['heritage-press-admin']);
        $this->log_test("Admin JS", $admin_js_exists ? 'Registered' : 'Missing', $admin_js_exists);
    }
    
    private function test_public_assets() {
        global $wp_styles, $wp_scripts;
        
        $public_css_exists = isset($wp_styles->registered['heritage-press']);
        $this->log_test("Public CSS", $public_css_exists ? 'Registered' : 'Missing', $public_css_exists);
        
        $public_js_exists = isset($wp_scripts->registered['heritage-press']);
        $this->log_test("Public JS", $public_js_exists ? 'Registered' : 'Missing', $public_js_exists);
    }
    
    private function estimate_plugin_load_time() {
        $start = microtime(true);
        // Simulate plugin operations
        if (class_exists('HeritagePress\\Core\\ServiceContainer')) {
            $container = new HeritagePress\Core\ServiceContainer();
        }
        $load_time = round((microtime(true) - $start) * 1000, 2);
        $this->log_test("Plugin Load Time", $load_time . " ms", $load_time < 50);
    }
    
    /**
     * Logging methods
     */
    private function log_header($title) {
        echo "<div style='background: #0073aa; color: white; padding: 20px; margin: 20px 0;'>";
        echo "<h1 style='margin: 0; color: white;'>🚀 $title</h1>";
        echo "<p style='margin: 5px 0 0 0; color: #e1f5fe;'>Date: " . current_time('Y-m-d H:i:s') . " | WordPress: " . get_bloginfo('version') . "</p>";
        echo "</div>";
    }
    
    private function log_section($section) {
        echo "<div style='background: #f1f1f1; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa;'>";
        echo "<h2 style='margin: 0; color: #23282d;'>📋 $section</h2>";
        echo "</div>";
    }
    
    private function log_test($test_name, $result, $passed) {
        $icon = $passed ? '✅' : '❌';
        $color = $passed ? '#46b450' : '#dc3232';
        
        echo "<div style='padding: 10px; margin: 5px 0; background: white; border: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;'>";
        echo "<span><strong>$test_name:</strong> $result</span>";
        echo "<span style='color: $color; font-size: 18px;'>$icon</span>";
        echo "</div>";
        
        $this->results[] = [
            'test' => $test_name,
            'result' => $result,
            'passed' => $passed
        ];
        
        if (!$passed) {
            $this->errors[] = $test_name;
        }
    }
    
    /**
     * Display comprehensive results
     */
    private function display_results() {
        $total_tests = count($this->results);
        $passed_tests = count(array_filter($this->results, function($r) { return $r['passed']; }));
        $failed_tests = $total_tests - $passed_tests;
        
        echo "<div style='background: #fff; padding: 20px; margin: 20px 0; border: 2px solid #0073aa;'>";
        echo "<h2>📊 Test Results Summary</h2>";
        echo "<div style='display: flex; gap: 20px; margin: 15px 0;'>";
        echo "<div style='background: #46b450; color: white; padding: 15px; border-radius: 5px; text-align: center;'>";
        echo "<h3 style='margin: 0; color: white;'>$passed_tests</h3><p style='margin: 5px 0 0 0;'>Passed</p></div>";
        echo "<div style='background: #dc3232; color: white; padding: 15px; border-radius: 5px; text-align: center;'>";
        echo "<h3 style='margin: 0; color: white;'>$failed_tests</h3><p style='margin: 5px 0 0 0;'>Failed</p></div>";
        echo "<div style='background: #0073aa; color: white; padding: 15px; border-radius: 5px; text-align: center;'>";
        echo "<h3 style='margin: 0; color: white;'>$total_tests</h3><p style='margin: 5px 0 0 0;'>Total</p></div>";
        echo "</div>";
        
        $success_rate = round(($passed_tests / $total_tests) * 100, 1);
        echo "<h3>Success Rate: $success_rate%</h3>";
        echo "</div>";
    }
    
    /**
     * Generate Phase 2 recommendations
     */
    private function generate_phase2_recommendations() {
        echo "<div style='background: #fff3cd; padding: 20px; margin: 20px 0; border: 2px solid #ffc107;'>";
        echo "<h2>🎯 Phase 2 Recommendations</h2>";
        
        if (empty($this->errors)) {
            echo "<div style='color: #155724; background: #d4edda; padding: 15px; border-radius: 5px;'>";
            echo "<strong>🎉 Excellent! WordPress integration is solid.</strong><br>";
            echo "Ready to proceed with Phase 2 enhancements:<br>";
            echo "• Enhanced GEDCOM 7.0 parser<br>";
            echo "• Advanced family management<br>";
            echo "• Public interface improvements<br>";
            echo "• Performance optimizations";
            echo "</div>";
        } else {
            echo "<div style='color: #721c24; background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "<strong>⚠️ Issues found that need attention:</strong><br>";
            foreach ($this->errors as $error) {
                echo "• $error<br>";
            }
            echo "</div>";
        }
        
        echo "<h3>Next Steps for Phase 2:</h3>";
        echo "<ol>";
        echo "<li><strong>GEDCOM Enhancement</strong> - Improve file upload and parsing</li>";
        echo "<li><strong>Family Management</strong> - Complete relationship interfaces</li>";
        echo "<li><strong>Public Templates</strong> - Build responsive frontend</li>";
        echo "<li><strong>Performance</strong> - Optimize for larger datasets</li>";
        echo "</ol>";
        echo "</div>";
    }
}

// Auto-run if accessed directly with proper WordPress context
if (defined('ABSPATH') && isset($_GET['heritage_phase2_test'])) {
    $tester = new HeritagePress_Phase2_LiveTester();
    $tester->run_all_tests();
    exit;
}
