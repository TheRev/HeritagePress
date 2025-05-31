<?php
/**
 * Main plugin class
 *
 * @package HeritagePress
 */

namespace HeritagePress\Core;

use HeritagePress\Core\Container;

/**
 * The main plugin class
 */
class Plugin {
    /**
     * The single instance of the plugin
     */
    private static $instance = null;

    /**
     * The dependency injection container
     */
    private $container;

    /**
     * Constructor
     */
    private function __construct() {
        $this->container = Container::getInstance();
        $this->register_services();
        $this->boot();
    }

    /**
     * Get the single instance of the plugin
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }    /**
     * Register services in the container
     */
    private function register_services() {        // Database
        $this->container->register('database.manager', function() {
            return new \HeritagePress\Database\DatabaseManager();
        });
        
        $this->container->register('database.gedcom', function() {
            return new \HeritagePress\Database\GedcomDatabaseHandler();
        });        // GEDCOM 
        $this->container->register('gedcom.parser', function() {
            return new \HeritagePress\GEDCOM\Gedcom7Parser();
        });
        
        $this->container->register('gedcom.validator', function() {
            return new \HeritagePress\GEDCOM\Gedcom7Validator();
        });

        $this->container->register('gedcom.exporter', function() {
            return new \HeritagePress\GEDCOM\GedcomExportHandler();
        });

        // Events
        $this->container->register('events', function() {
            return new \HeritagePress\Core\GedcomEvents();
        });        // Admin
        $this->container->register('admin.evidence', function() {
            return new \HeritagePress\Admin\Evidence_Admin();
        });
    }    /**
     * Boot the plugin
     */
    private function boot() {
        // Initialize any needed hooks/filters
        // Note: init hook is added in run() method to avoid duplication
    }/**
     * Initialize plugin
     */
    public function init() {
        // Initialize admin interfaces if in admin area
        if (is_admin()) {
            $this->init_admin();
        }
    }    /**
     * Initialize admin interfaces
     */
    private function init_admin() {
        // Add main Heritage Press admin menu first
        add_action('admin_menu', [$this, 'add_admin_menus'], 10);
        
        // Initialize Evidence Admin
        $evidence_admin = $this->container->get('admin.evidence');
        $evidence_admin->init();
    }
    
    /**
     * Add main Heritage Press admin menus
     */
    public function add_admin_menus() {
        // Main Heritage Press menu
        add_menu_page(
            __('Heritage Press', 'heritage-press'),
            __('Heritage Press', 'heritage-press'),
            'manage_options',
            'heritage-press',
            [$this, 'render_main_admin_page'],
            'dashicons-groups',
            30
        );

        // Individuals submenu
        add_submenu_page(
            'heritage-press',
            __('Individuals', 'heritage-press'),
            __('Individuals', 'heritage-press'),
            'manage_options',
            'heritage-press-individuals',
            [$this, 'render_individuals_page']
        );
        
        // Families submenu
        add_submenu_page(
            'heritage-press',
            __('Families', 'heritage-press'),
            __('Families', 'heritage-press'),
            'manage_options',
            'heritage-press-families',
            [$this, 'render_families_page']
        );
        
        // Import submenu
        add_submenu_page(
            'heritage-press',
            __('Import', 'heritage-press'),
            __('Import', 'heritage-press'),
            'manage_options',
            'heritage-press-import',
            [$this, 'render_import_page']
        );
    }
    
    /**
     * Render main admin page
     */
    public function render_main_admin_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/main-admin.php';
    }
    
    /**
     * Render individuals page
     */
    public function render_individuals_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/individuals.php';
    }
    
    /**
     * Render families page
     */
    public function render_families_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/families.php';
    }
    
    /**
     * Render import page
     */
    public function render_import_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/import.php';
    }    /**
     * Run the plugin
     */
    public function run() {
        // Make sure init hook is added to initialize admin menus
        add_action('init', [$this, 'init']);
    }
}
