<?php
/**
 * Add Table Verification tool to WordPress admin menu
 *
 * @package HeritagePress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Add Table Verification menu item to the Heritage Press admin menu
 */
function heritage_press_add_table_verification_menu() {
    add_submenu_page(
        'heritage-press',
        __('Database Table Verification', 'heritage-press'),
        __('Table Verification', 'heritage-press'),
        'manage_options',
        'heritage-table-verification',
        'heritage_press_render_table_verification_page'
    );
}
add_action('admin_menu', 'heritage_press_add_table_verification_menu');

/**
 * Render the Table Verification page
 */
function heritage_press_render_table_verification_page() {
    // Include and run the verification
    require_once HERITAGE_PRESS_PLUGIN_DIR . 'admin/tools/table-verification.php';
}
