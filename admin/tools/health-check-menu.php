<?php
/**
 * Heritage Press Health Check Menu
 *
 * Adds the Health Check tool to the Heritage Press admin menu.
 *
 * @package HeritagePress\Admin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Health Check menu item to the Heritage Press admin menu
 */
function heritage_press_add_health_check_menu() {
    add_submenu_page(
        'heritage-press',
        __('System Health Check', 'heritage-press'),
        __('Health Check', 'heritage-press'),
        'manage_options',
        'heritage-health-check',
        'heritage_press_render_health_check_page'
    );
}
add_action('admin_menu', 'heritage_press_add_health_check_menu');

/**
 * Render the Health Check page
 */
function heritage_press_render_health_check_page() {
    // Load and run the health check
    require_once HERITAGE_PRESS_PLUGIN_DIR . 'admin/tools/heritage-press-health-check.php';
}
