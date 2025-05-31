<?php
/**
 * Family Tree Test Menu
 * 
 * Adds a menu item for testing the family tree generator.
 * 
 * @package HeritagePress\Admin\Tools
 */

namespace HeritagePress\Admin\Tools;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add menu item for testing family tree generator
 */
function add_family_tree_test_menu() {
    if (!current_user_can('manage_options')) {
        return;
    }

    add_submenu_page(
        'heritage-press-tools',             // Parent menu slug
        'Family Tree Test',                 // Page title
        'Family Tree Test',                 // Menu title
        'manage_options',                   // Capability
        'heritage-press-family-tree-test',  // Menu slug
        __NAMESPACE__ . '\display_family_tree_test_page' // Function to display the page
    );
}
add_action('admin_menu', __NAMESPACE__ . '\add_family_tree_test_menu', 25);

/**
 * Display the family tree test page
 */
function display_family_tree_test_page() {
    // Verify user has permission
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'heritage-press'));
    }
    
    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Family Tree Generator Test', 'heritage-press') . '</h1>';
    
    echo '<p>' . esc_html__('This tool allows you to test the family tree generator by creating a sample family structure and visualizing different tree types.', 'heritage-press') . '</p>';
    
    // Include and run the test script
    include_once(HERITAGE_PRESS_PLUGIN_DIR . 'admin/tools/test-family-tree-generator.php');
    
    echo '</div>';
}
