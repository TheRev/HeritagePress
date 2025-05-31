<?php
/**
 * Heritage Press - Phase 2 Live Testing Menu
 * Adds Phase 2 WordPress live testing to admin menu
 * 
 * @package HeritagePress
 * @version 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Phase 2 live testing to admin menu
 */
function heritage_press_add_phase2_live_testing_menu() {
    add_submenu_page(
        'heritage-press',
        'Phase 2 Live Testing',
        '🚀 Phase 2 Testing',
        'manage_heritage_press',
        'heritage-phase2-live-testing',
        'heritage_press_phase2_live_testing_page'
    );
}
add_action('admin_menu', 'heritage_press_add_phase2_live_testing_menu');

/**
 * Display Phase 2 live testing page
 */
function heritage_press_phase2_live_testing_page() {
    ?>
    <div class="wrap">
        <h1>🚀 Heritage Press - Phase 2 Live Testing</h1>
        <p>Comprehensive WordPress integration testing for Phase 2 development</p>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
            <h2>Phase 2 Testing Overview</h2>
            <p>This tool performs comprehensive testing of Heritage Press in your live WordPress environment:</p>
            <ul>
                <li><strong>WordPress Environment</strong> - Version compatibility and requirements</li>
                <li><strong>Plugin Activation</strong> - Verify all components are loaded correctly</li>
                <li><strong>Database Integration</strong> - Test table creation and data access</li>
                <li><strong>Admin Interface</strong> - Check menu registration and permissions</li>
                <li><strong>AJAX Endpoints</strong> - Verify all AJAX functions are working</li>
                <li><strong>Public Interface</strong> - Test frontend components and shortcodes</li>
                <li><strong>GEDCOM Functionality</strong> - Verify file upload and parsing capabilities</li>
                <li><strong>Performance Metrics</strong> - Check memory usage and query performance</li>
            </ul>
        </div>
        
        <div style="background: #e7f3ff; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa;">
            <h3>🎯 Phase 2 Goals</h3>
            <p>Based on test results, Phase 2 will focus on:</p>
            <ol>
                <li><strong>Enhanced GEDCOM Parser</strong> - Full GEDCOM 7.0 support with error recovery</li>
                <li><strong>Family Management System</strong> - Complete relationship management interface</li>
                <li><strong>Public Interface</strong> - Responsive genealogy displays for visitors</li>
                <li><strong>Performance Optimization</strong> - Handle larger datasets efficiently</li>
            </ol>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?php echo admin_url('admin.php?page=heritage-phase2-live-testing&heritage_phase2_test=1'); ?>" 
               class="button button-primary button-hero" 
               style="padding: 15px 30px; font-size: 16px;">
               🧪 Run Phase 2 Live Tests
            </a>
        </div>
        
        <div style="background: #fff3cd; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107;">
            <h4>⚠️ Testing Notes</h4>
            <ul>
                <li>This test runs in your live WordPress environment</li>
                <li>No data will be modified during testing</li>
                <li>Results will help identify Phase 2 priorities</li>
                <li>Testing typically takes 10-30 seconds</li>
            </ul>
        </div>
    </div>
    
    <?php
    // Run the actual tests if requested
    if (isset($_GET['heritage_phase2_test'])) {
        echo "<div style='margin-top: 30px;'>";
        require_once plugin_dir_path(__FILE__) . 'phase2-wordpress-live-tester.php';
        $tester = new HeritagePress_Phase2_LiveTester();
        $tester->run_all_tests();
        echo "</div>";
    }
}

/**
 * Add admin notice for Phase 2 testing
 */
function heritage_press_phase2_testing_notice() {
    if (!current_user_can('manage_heritage_press')) {
        return;
    }
    
    // Only show on Heritage Press pages
    $screen = get_current_screen();
    if (strpos($screen->id, 'heritage') === false) {
        return;
    }
    
    ?>
    <div class="notice notice-info is-dismissible">
        <h3>🚀 Phase 2 Development Started!</h3>
        <p>
            <strong>Heritage Press Phase 2</strong> focuses on enhanced GEDCOM integration and WordPress optimization.
            <a href="<?php echo admin_url('admin.php?page=heritage-phase2-live-testing'); ?>" class="button button-primary">
                Run Phase 2 Tests
            </a>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'heritage_press_phase2_testing_notice');
