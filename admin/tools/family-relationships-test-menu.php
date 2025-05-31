<?php
/**
 * Family Relationships Test Menu
 * 
 * Adds a menu item for testing the family relationships system.
 * 
 * @package HeritagePress\Admin\Tools
 */

namespace HeritagePress\Admin\Tools;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add menu item for testing family relationships
 */
function add_family_relationships_test_menu() {
    if (!current_user_can('manage_options')) {
        return;
    }

    add_submenu_page(
        'heritage-press-tools',             // Parent menu slug
        'Family Relationships Test',        // Page title
        'Family Relationships Test',        // Menu title
        'manage_options',                  // Capability
        'heritage-press-family-relationships-test', // Menu slug
        __NAMESPACE__ . '\display_family_relationships_test_page' // Function to display the page
    );
}
add_action('admin_menu', __NAMESPACE__ . '\add_family_relationships_test_menu', 20);

/**
 * Display the family relationships test page
 */
function display_family_relationships_test_page() {
    // Verify user has permission
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'heritage-press'));
    }
    
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Family Relationships Test', 'heritage-press') . '</h1>';
    
    echo '<p>' . esc_html__('This tool allows you to test the family relationships system by creating a sample family structure.', 'heritage-press') . '</p>';
    
    if (isset($_POST['run_test']) && wp_verify_nonce($_POST['family_relationships_test_nonce'], 'family_relationships_test')) {
        // Run the test
        echo '<div class="notice notice-info"><p>' . esc_html__('Running test...', 'heritage-press') . '</p></div>';
        echo '<h2>' . esc_html__('Test Results', 'heritage-press') . '</h2>';
        echo '<pre style="background:#f6f6f6; padding:15px; overflow:auto; max-height:400px;">';
        
        // Include and run the test script
        include_once(HERITAGE_PRESS_PLUGIN_DIR . 'admin/tools/test-family-relationships.php');
        
        echo '</pre>';
    }
    
    // Test form
    echo '<form method="post">';
    wp_nonce_field('family_relationships_test', 'family_relationships_test_nonce');
    echo '<p class="submit">';
    echo '<input type="submit" name="run_test" class="button button-primary" value="' . esc_attr__('Run Family Relationships Test', 'heritage-press') . '" />';
    echo ' <input type="submit" name="run_test_and_cleanup" class="button" value="' . esc_attr__('Run Test and Clean Up Data', 'heritage-press') . '" />';
    echo '</p>';
    echo '</form>';
    
    echo '<h2>' . esc_html__('About the Family Relationship System', 'heritage-press') . '</h2>';
    echo '<p>' . esc_html__('The Heritage Press family relationship system provides a flexible way to represent complex family structures, including:', 'heritage-press') . '</p>';
    echo '<ul style="list-style-type: disc; margin-left: 20px;">';
    echo '<li>' . esc_html__('Multiple types of partnerships (marriage, civil union, domestic partnership, etc.)', 'heritage-press') . '</li>';
    echo '<li>' . esc_html__('Multiple types of parent-child relationships (birth, adoption, foster, etc.)', 'heritage-press') . '</li>';
    echo '<li>' . esc_html__('Support for non-traditional and blended families', 'heritage-press') . '</li>';
    echo '<li>' . esc_html__('Historical and cultural variations in family structures', 'heritage-press') . '</li>';
    echo '</ul>';
    
    echo '<p>' . esc_html__('This flexible approach ensures that Heritage Press can represent the full range of family relationships found in genealogical research.', 'heritage-press') . '</p>';
    
    echo '</div>';
}
