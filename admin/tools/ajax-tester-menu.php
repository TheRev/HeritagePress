<?php
/**
 * Add AJAX Endpoint Tester to WordPress admin menu
 *
 * @package HeritagePress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Add AJAX Endpoint Tester menu item to the Heritage Press admin menu
 */
function heritage_press_add_ajax_tester_menu() {
    add_submenu_page(
        'heritage-press',
        __('AJAX Endpoint Tester', 'heritage-press'),
        __('AJAX Tester', 'heritage-press'),
        'manage_options',
        'heritage-ajax-tester',
        'heritage_press_render_ajax_tester_page'
    );
}
add_action('admin_menu', 'heritage_press_add_ajax_tester_menu');

/**
 * Render the AJAX Endpoint Tester page
 */
function heritage_press_render_ajax_tester_page() {
    // Include and run the tester
    require_once HERITAGE_PRESS_PLUGIN_DIR . 'admin/tools/ajax-endpoint-tester.php';
}
