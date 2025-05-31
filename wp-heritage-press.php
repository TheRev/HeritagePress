<?php
/**
 * Plugin Name: Heritage Press
 * Plugin URI: https://heritagepress.org
 * Description: A comprehensive genealogy management system for WordPress that helps you organize family history, import GEDCOM files, and manage family relationships.
 * Version: 1.0.0
 * Author: Heritage Press Team
 * Author URI: https://heritagepress.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: heritage-press
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Network: false
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HERITAGE_PRESS_VERSION', '1.0.0');
define('HERITAGE_PRESS_PLUGIN_FILE', __FILE__);
define('HERITAGE_PRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HERITAGE_PRESS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HERITAGE_PRESS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Heritage Press Plugin Class
 */
class Heritage_Press {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_database_tables();
        $this->create_default_options();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('heritage-press', false, dirname(HERITAGE_PRESS_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        $this->load_includes();
    }
    
    /**
     * Load required files
     */
    private function load_includes() {
        require_once HERITAGE_PRESS_PLUGIN_DIR . 'includes/class-individual.php';
        require_once HERITAGE_PRESS_PLUGIN_DIR . 'includes/class-family.php';
        require_once HERITAGE_PRESS_PLUGIN_DIR . 'includes/class-gedcom-parser.php';
        require_once HERITAGE_PRESS_PLUGIN_DIR . 'includes/class-admin.php';
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Individuals table
        $individuals_table = $wpdb->prefix . 'heritage_individuals';
        $individuals_sql = "CREATE TABLE $individuals_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            gedcom_id varchar(50) NOT NULL,
            first_name varchar(100) DEFAULT '',
            last_name varchar(100) DEFAULT '',
            gender enum('M','F','U') DEFAULT 'U',
            birth_date varchar(50) DEFAULT '',
            birth_place varchar(255) DEFAULT '',
            death_date varchar(50) DEFAULT '',
            death_place varchar(255) DEFAULT '',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY gedcom_id (gedcom_id),
            KEY name_search (first_name, last_name)
        ) $charset_collate;";
        
        // Families table
        $families_table = $wpdb->prefix . 'heritage_families';
        $families_sql = "CREATE TABLE $families_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            gedcom_id varchar(50) NOT NULL,
            husband_id mediumint(9) DEFAULT NULL,
            wife_id mediumint(9) DEFAULT NULL,
            marriage_date varchar(50) DEFAULT '',
            marriage_place varchar(255) DEFAULT '',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY gedcom_id (gedcom_id),
            KEY husband_id (husband_id),
            KEY wife_id (wife_id)
        ) $charset_collate;";
        
        // Family relationships table
        $relationships_table = $wpdb->prefix . 'heritage_relationships';
        $relationships_sql = "CREATE TABLE $relationships_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            family_id mediumint(9) NOT NULL,
            individual_id mediumint(9) NOT NULL,
            relationship_type enum('child','parent') NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY family_id (family_id),
            KEY individual_id (individual_id),
            UNIQUE KEY unique_relationship (family_id, individual_id, relationship_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($individuals_sql);
        dbDelta($families_sql);
        dbDelta($relationships_sql);
    }
    
    /**
     * Create default plugin options
     */
    private function create_default_options() {
        $default_options = array(
            'version' => HERITAGE_PRESS_VERSION,
            'enable_frontend_display' => true,
            'date_format' => 'Y-m-d',
            'privacy_mode' => false
        );
        
        add_option('heritage_press_options', $default_options);
    }
    
    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_menu_page(
            __('Heritage Press', 'heritage-press'),
            __('Heritage Press', 'heritage-press'),
            'manage_options',
            'heritage-press',
            array($this, 'admin_dashboard_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'heritage-press',
            __('Individuals', 'heritage-press'),
            __('Individuals', 'heritage-press'),
            'manage_options',
            'heritage-press-individuals',
            array($this, 'admin_individuals_page')
        );
        
        add_submenu_page(
            'heritage-press',
            __('Families', 'heritage-press'),
            __('Families', 'heritage-press'),
            'manage_options',
            'heritage-press-families',
            array($this, 'admin_families_page')
        );
        
        add_submenu_page(
            'heritage-press',
            __('Import GEDCOM', 'heritage-press'),
            __('Import GEDCOM', 'heritage-press'),
            'manage_options',
            'heritage-press-import',
            array($this, 'admin_import_page')
        );
        
        add_submenu_page(
            'heritage-press',
            __('Settings', 'heritage-press'),
            __('Settings', 'heritage-press'),
            'manage_options',
            'heritage-press-settings',
            array($this, 'admin_settings_page')
        );
    }
    
    /**
     * Dashboard page
     */
    public function admin_dashboard_page() {
        global $wpdb;
        
        $individuals_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}heritage_individuals");
        $families_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}heritage_families");
        
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Individuals page
     */
    public function admin_individuals_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/individuals.php';
    }
    
    /**
     * Families page
     */
    public function admin_families_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/families.php';
    }
    
    /**
     * Import page
     */
    public function admin_import_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/import.php';
    }
    
    /**
     * Settings page
     */
    public function admin_settings_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'heritage-press') === false) {
            return;
        }
        
        wp_enqueue_style(
            'heritage-press-admin',
            HERITAGE_PRESS_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            HERITAGE_PRESS_VERSION
        );
        
        wp_enqueue_script(
            'heritage-press-admin',
            HERITAGE_PRESS_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            HERITAGE_PRESS_VERSION,
            true
        );
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_scripts() {
        wp_enqueue_style(
            'heritage-press-frontend',
            HERITAGE_PRESS_PLUGIN_URL . 'public/css/frontend.css',
            array(),
            HERITAGE_PRESS_VERSION
        );
        
        wp_enqueue_script(
            'heritage-press-frontend',
            HERITAGE_PRESS_PLUGIN_URL . 'public/js/frontend.js',
            array('jquery'),
            HERITAGE_PRESS_VERSION,
            true
        );
    }
}

// Initialize the plugin
Heritage_Press::get_instance();
