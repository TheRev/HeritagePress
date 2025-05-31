<?php
/**
 * WordPress Compatibility Helper
 * 
 * This file provides compatibility stubs for WordPress functions
 * to reduce IDE errors when analyzing code outside WordPress context.
 * These functions are only defined if they don't already exist.
 *
 * @package HeritagePress
 */

// Exit if accessed directly
if (!defined('ABSPATH') && !defined('WPINC')) {
    // Define basic constants for standalone analysis
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__FILE__, 6) . '/');
    }
    if (!defined('WPINC')) {
        define('WPINC', 'wp-includes');
    }
}

// Define WordPress time constants
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 604800);
}
if (!defined('MONTH_IN_SECONDS')) {
    define('MONTH_IN_SECONDS', 2592000);
}
if (!defined('YEAR_IN_SECONDS')) {
    define('YEAR_IN_SECONDS', 31536000);
}

// Define WordPress database output constants
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}
if (!defined('OBJECT_K')) {
    define('OBJECT_K', 'OBJECT_K');
}
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}

/**
 * WordPress Admin Functions
 */
if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null) {
        return null;
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '') {
        return null;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {
        return 'http://localhost/wordpress/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wordpress/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

/**
 * WordPress Enqueue Functions
 */
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        return null;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        return null;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        return null;
    }
}

/**
 * WordPress Security Functions
 */
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'dummy_nonce_' . time();
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true;
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = "_wpnonce", $referer = true, $echo = true) {
        $nonce = wp_create_nonce($action);
        $output = '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . $nonce . '" />';
        if ($echo) {
            echo $output;
        }
        return $output;
    }
}

/**
 * WordPress Sanitization Functions
 */
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return strip_tags(trim($str));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return strip_tags(trim($str));
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name($filename) {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }
}

/**
 * WordPress Redirect and Die Functions
 */
if (!function_exists('wp_redirect')) {
    function wp_redirect($location, $status = 302, $x_redirect_by = 'WordPress') {
        if (headers_sent()) {
            return false;
        }
        header("Location: $location", true, $status);
        return true;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        if (is_array($title)) {
            $args = $title;
            $title = '';
        }
        die($message);
    }
}

/**
 * WordPress File and Upload Functions
 */
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir($time = null, $create_dir = true, $refresh_cache = false) {
        $basedir = ABSPATH . 'wp-content/uploads';
        $baseurl = 'http://localhost/wordpress/wp-content/uploads';
        
        if ($time) {
            $subdir = '/' . date('Y/m', $time);
        } else {
            $subdir = '/' . date('Y/m');
        }
        
        return array(
            'path'    => $basedir . $subdir,
            'url'     => $baseurl . $subdir,
            'subdir'  => $subdir,
            'basedir' => $basedir,
            'baseurl' => $baseurl,
            'error'   => false,
        );
    }
}

if (!function_exists('wp_max_upload_size')) {
    function wp_max_upload_size() {
        $u_bytes = wp_convert_hr_to_bytes(ini_get('upload_max_filesize'));
        $p_bytes = wp_convert_hr_to_bytes(ini_get('post_max_size'));
        return min($u_bytes, $p_bytes);
    }
}

if (!function_exists('wp_convert_hr_to_bytes')) {
    function wp_convert_hr_to_bytes($size) {
        $size = strtolower($size);
        $bytes = (int) $size;
        
        if (strpos($size, 'k') !== false) {
            $bytes = intval($size) * 1024;
        } elseif (strpos($size, 'm') !== false) {
            $bytes = intval($size) * 1024 * 1024;
        } elseif (strpos($size, 'g') !== false) {
            $bytes = intval($size) * 1024 * 1024 * 1024;
        }
        
        return $bytes;
    }
}

if (!function_exists('wp_handle_sideload')) {
    function wp_handle_sideload($file, $overrides = array(), $time = null) {
        return array(
            'file' => $file['tmp_name'],
            'url' => 'http://localhost/wordpress/wp-content/uploads/dummy-file.jpg',
            'type' => $file['type']
        );
    }
}

if (!function_exists('wp_insert_attachment')) {
    function wp_insert_attachment($attachment, $filename = false, $parent = 0, $wp_error = false) {
        return rand(1, 1000); // Dummy attachment ID
    }
}

if (!function_exists('wp_generate_attachment_metadata')) {
    function wp_generate_attachment_metadata($attachment_id, $file) {
        return array();
    }
}

if (!function_exists('wp_update_attachment_metadata')) {
    function wp_update_attachment_metadata($attachment_id, $data) {
        return true;
    }
}

/**
 * WordPress Database Functions
 */
if (!isset($wpdb)) {
    global $wpdb;
    $wpdb = new stdClass();
    $wpdb->prefix = 'wp_';
    $wpdb->last_error = '';
}

/**
 * WordPress Options Functions
 */
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

if (!function_exists('add_option')) {
    function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        return true;
    }
}

/**
 * WordPress Hook Functions
 */
if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action($tag, ...$args) {
        return null;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) {
        return $value;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $function) {
        return null;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $function) {
        return null;
    }
}

/**
 * WordPress Translation Functions
 */
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo __($text, $domain);
    }
}

if (!function_exists('_x')) {
    function _x($text, $context, $domain = 'default') {
        return __($text, $domain);
    }
}

if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default') {
        return ($number == 1) ? $single : $plural;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url, $protocols = null, $_context = 'display') {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

/**
 * WordPress Utility Functions
 */
if (!function_exists('wp_generate_uuid4')) {
    function wp_generate_uuid4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array()) {
        if (is_object($args)) {
            $parsed_args = get_object_vars($args);
        } elseif (is_array($args)) {
            $parsed_args = &$args;
        } else {
            parse_str($args, $parsed_args);
        }
        
        if (is_array($defaults)) {
            return array_merge($defaults, $parsed_args);
        }
        return $parsed_args;
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) {
        return mkdir($target, 0755, true);
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        switch ($type) {
            case 'mysql':
                return date('Y-m-d H:i:s');
            case 'timestamp':
                return time();
            default:
                return date($type);
        }
    }
}

if (!function_exists('mysql2date')) {
    function mysql2date($format, $date, $translate = true) {
        if (empty($date)) {
            return false;
        }
        
        $datetime = date_create($date);
        if (!$datetime) {
            return false;
        }
        
        return $datetime->format($format);
    }
}

/**
 * WordPress AJAX Functions
 */
if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {
        $response = array('success' => true);
        if (isset($data)) {
            $response['data'] = $data;
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) {
        $response = array('success' => false);
        if (isset($data)) {
            $response['data'] = $data;
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

if (!function_exists('wp_send_json')) {
    function wp_send_json($response, $status_code = null) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

/**
 * WordPress Error Functions
 */
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return ($thing instanceof WP_Error);
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = array();
        public $error_data = array();
        
        public function __construct($code = '', $message = '', $data = '') {
            if (empty($code)) {
                return;
            }
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }
        
        public function get_error_code() {
            $codes = $this->get_error_codes();
            if (empty($codes)) {
                return '';
            }
            return $codes[0];
        }
        
        public function get_error_codes() {
            if (empty($this->errors)) {
                return array();
            }
            return array_keys($this->errors);
        }
        
        public function get_error_message($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            if (isset($this->errors[$code])) {
                return $this->errors[$code][0];
            }
            return '';
        }
    }
}

/**
 * WordPress Media Functions
 */
if (!function_exists('get_allowed_mime_types')) {
    function get_allowed_mime_types($user = null) {
        return array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );
    }
}

if (!function_exists('wpautop')) {
    function wpautop($pee, $br = true) {
        return '<p>' . str_replace("\n", "</p>\n<p>", trim($pee)) . '</p>';
    }
}

/**
 * WordPress Transient Functions
 */
if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($transient) {
        return false; // Return false to indicate no cached data
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        return true;
    }
}

/**
 * WordPress Misc Functions
 */
if (!function_exists('checked')) {
    function checked($checked, $current = true, $echo = true) {
        return __checked_selected_helper($checked, $current, $echo, 'checked');
    }
}

if (!function_exists('selected')) {
    function selected($selected, $current = true, $echo = true) {
        return __checked_selected_helper($selected, $current, $echo, 'selected');
    }
}

if (!function_exists('__checked_selected_helper')) {
    function __checked_selected_helper($helper, $current, $echo, $type) {
        if ((string) $helper === (string) $current) {
            $result = " $type='$type'";
        } else {
            $result = '';
        }
        
        if ($echo) {
            echo $result;
        }
        
        return $result;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return true; // Assume admin context for IDE analysis
    }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false) {
        return true;
    }
}

/**
 * Mock wpdb class for IDE compatibility
 */
if (!class_exists('wpdb')) {
    class wpdb {
        public $prefix = 'wp_';
        public $insert_id = 1;
        public $last_error = '';
          public function delete($table, $where, $where_format = null) {
            return true;
        }
        
        public function insert($table, $data, $format = null) {
            return true;
        }
        
        public function update($table, $data, $where, $format = null, $where_format = null) {
            return true;
        }
        
        public function query($query) {
            return true;
        }
        
        public function prepare($query, ...$args) {
            return $query;
        }
        
        public function get_results($query, $output = OBJECT) {
            return array();
        }
        
        public function get_row($query, $output = OBJECT, $y = 0) {
            return null;
        }
          public function get_var($query, $x = 0, $y = 0) {
            return null;
        }
        
        public function get_col($query, $x = 0) {
            return array();
        }
        
        public function get_charset_collate() {
            return 'DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
        }
        
        public function esc_like($text) {
            return addslashes($text);
        }
    }
}

// Create global $wpdb if it doesn't exist
if (!isset($GLOBALS['wpdb'])) {
    $GLOBALS['wpdb'] = new wpdb();
}
