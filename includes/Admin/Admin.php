<?php
/**
 * Admin Interface Manager
 *
 * Handles all WordPress backend administration functionality
 * for the HeritagePress genealogy plugin.
 *
 * @package HeritagePress
 * @subpackage Admin
 * @since 1.0.0
 */

namespace HeritagePress\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use HeritagePress\Database\Manager as SchemaManager;
use HeritagePress\Database\WPHelper;
use HeritagePress\Admin\MenuManager;
use HeritagePress\Admin\AssetManager;
use HeritagePress\Admin\FormHandler;
use HeritagePress\Admin\PageRenderer;
use HeritagePress\Admin\AjaxHandler;
use HeritagePress\Admin\DatabaseOperations;

if (!defined('HERITAGEPRESS_VERSION')) {
    define('HERITAGEPRESS_VERSION', '1.0.0');
}

/**
 * Main admin class for Heritage Press plugin
 */
class Admin
{
    /** @var SchemaManager */
    private $db_manager;

    /** @var MenuManager */
    private $menu_manager;

    /** @var AssetManager */
    private $asset_manager;

    /** @var FormHandler */
    private $form_handler;

    /** @var Admin Singleton instance */
    private static $instance = null;

    /** @var boolean Flag to track if an instance was already created */
    private static $instance_created = false;

    /** @var PageRenderer */
    private $page_renderer;

    /** @var AjaxHandler */
    private $ajax_handler;    /**
          * Initialize the admin interface
          *
          * @param string $plugin_path Main plugin directory path
          * @param string $version Plugin version
          */    /**
               * Get the singleton instance
               *
               * @param string $plugin_path Main plugin directory path
               * @param string $version Plugin version
               * @return Admin The singleton instance
               */
    public static function get_instance($plugin_path, $version = '1.0.0')
    {
        // Generate a unique runtime ID for this request if not already set
        if (!defined('HERITAGEPRESS_ADMIN_INSTANCE_KEY')) {
            define('HERITAGEPRESS_ADMIN_INSTANCE_KEY', 'hp_admin_instance_' . HERITAGEPRESS_REQUEST_ID);
        }

        // If we already have an instance for this request
        if (defined('HERITAGEPRESS_ADMIN_INSTANCE_CREATED') && self::$instance !== null) {
            return self::$instance;
        }

        // Check if we've already created an instance in this request
        if (defined('HERITAGEPRESS_ADMIN_INSTANCE_CREATED')) {
            self::$instance = new self($plugin_path, $version);
            return self::$instance;
        }

        // First time creating the instance in this request
        define('HERITAGEPRESS_ADMIN_INSTANCE_CREATED', true);
        self::$instance = new self($plugin_path, $version);
        return self::$instance;
    }

    /**
     * Constructor is private to enforce singleton pattern
     * 
     * @param string $plugin_path Main plugin directory path
     * @param string $version Plugin version
     */
    private function __construct($plugin_path, $version = '1.0.0')
    {

        // Calculate plugin URL
        if (!defined('HERITAGEPRESS_PLUGIN_URL')) {
            define('HERITAGEPRESS_PLUGIN_URL', WPHelper::getPluginUrl($plugin_path . 'heritagepress.php'));
        }
        $plugin_url = HERITAGEPRESS_PLUGIN_URL;

        // Initialize database manager and operations
        $this->db_manager = new SchemaManager($plugin_path, $version);
        $db_ops = new class ($this->db_manager) {
            use DatabaseOperations;
            private $wpdb;
            public function __construct($db_manager)
            {
                $this->wpdb = $db_manager->get_wpdb();
            }
        };

        // Initialize form handler with database access
        $this->form_handler = new FormHandler($this->db_manager);

        // Initialize other managers
        $this->menu_manager = new MenuManager();
        $this->asset_manager = new AssetManager($plugin_url);

        // Initialize page renderer with dependencies
        $this->page_renderer = new PageRenderer(
            $plugin_path,
            $db_ops,
            $this->form_handler
        );

        // Initialize AJAX handler with dependencies
        $this->ajax_handler = new AjaxHandler($this->db_manager, $db_ops);

        // Initialize components
        $this->init();
    }    /**
         * Initialize admin components
         */
    private function init()
    {

        // Add menu items
        WPHelper::addAction('admin_menu', [$this->menu_manager, 'register_menus']);

        // Register assets
        WPHelper::addAction('admin_enqueue_scripts', [$this->asset_manager, 'enqueue_assets']);

        // Initialize AJAX handlers
        WPHelper::addAction('wp_ajax_heritagepress_delete_individuals', [$this->ajax_handler, 'handle_delete_individuals']);
        WPHelper::addAction('wp_ajax_heritagepress_search_individuals', [$this->ajax_handler, 'handle_search_individuals']);
    }
}
