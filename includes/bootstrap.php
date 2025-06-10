<?php
/**
 * Bootstrap file to load WordPress dependencies
 * 
 * @package HeritagePress
 */

if (!defined('WPINC')) {
    die;
}

// Ensure core WordPress files and classes are loaded
global $wpdb;

// Required WordPress core files
require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once ABSPATH . 'wp-includes/l10n.php';
require_once ABSPATH . 'wp-includes/plugin.php';
require_once ABSPATH . 'wp-includes/functions.php';
require_once ABSPATH . 'wp-includes/pluggable.php';
require_once ABSPATH . 'wp-includes/link-template.php';
require_once ABSPATH . 'wp-includes/formatting.php';
require_once ABSPATH . 'wp-includes/script-loader.php';
require_once ABSPATH . 'wp-includes/class-wpdb.php';

// Required WordPress functions
if (!function_exists('plugin_dir_path')) {
    require_once ABSPATH . 'wp-includes/plugin.php';
}
if (!function_exists('plugins_url')) {
    require_once ABSPATH . 'wp-includes/plugin.php';
}
if (!function_exists('add_menu_page')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if (!function_exists('wp_enqueue_style')) {
    require_once ABSPATH . 'wp-includes/script-loader.php';
}
if (!function_exists('wp_enqueue_script')) {
    require_once ABSPATH . 'wp-includes/script-loader.php';
}
if (!function_exists('wp_create_nonce')) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}
if (!function_exists('check_admin_referer')) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}
if (!function_exists('wp_redirect')) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}
if (!function_exists('admin_url')) {
    require_once ABSPATH . 'wp-includes/link-template.php';
}
if (!function_exists('__')) {
    require_once ABSPATH . 'wp-includes/l10n.php';
}
if (!function_exists('_e')) {
    require_once ABSPATH . 'wp-includes/l10n.php';
}
if (!function_exists('esc_url')) {
    require_once ABSPATH . 'wp-includes/formatting.php';
}
if (!function_exists('esc_html')) {
    require_once ABSPATH . 'wp-includes/formatting.php';
}
if (!function_exists('esc_attr')) {
    require_once ABSPATH . 'wp-includes/formatting.php';
}
if (!function_exists('esc_attr_e')) {
    require_once ABSPATH . 'wp-includes/formatting.php';
}
if (!function_exists('sanitize_text_field')) {
    require_once ABSPATH . 'wp-includes/formatting.php';
}
if (!function_exists('sanitize_textarea_field')) {
    require_once ABSPATH . 'wp-includes/formatting.php';
}
if (!function_exists('add_query_arg')) {
    require_once ABSPATH . 'wp-includes/functions.php';
}
if (!function_exists('current_time')) {
    require_once ABSPATH . 'wp-includes/functions.php';
}
if (!function_exists('get_current_user_id')) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}
if (!function_exists('number_format_i18n')) {
    require_once ABSPATH . 'wp-includes/functions.php';
}
if (!function_exists('date_i18n')) {
    require_once ABSPATH . 'wp-includes/functions.php';
}
