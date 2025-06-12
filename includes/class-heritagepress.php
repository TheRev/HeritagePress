<?php
// Prevent multiple loads of the main class file
if (defined('HERITAGEPRESS_MAIN_CLASS_LOADED')) {
    return;
}
define('HERITAGEPRESS_MAIN_CLASS_LOADED', true);

/**
 * Main plugin class
 */

use HeritagePress\Database\WPHelper;

class HeritagePress
{
    /**
     * Plugin version
     */
    const VERSION = '1.0.0';    /**
            * Flag to track whether plugin has been initialized
            */
    private static $initialized = false;

    /**
     * Flag to track whether components have been initialized
     */
    private static $components_initialized = false;    /**
             * Plugin initialization
             */
    public static function init()
    {
        // Check if we've defined an initialization marker for this request
        if (!defined('HERITAGEPRESS_INIT_KEY')) {
            define('HERITAGEPRESS_INIT_KEY', 'hp_init_' . HERITAGEPRESS_REQUEST_ID);
        }

        // Prevent multiple initializations per request using a constant
        if (defined('HERITAGEPRESS_INITIALIZED')) {
            return;
        }
        define('HERITAGEPRESS_INITIALIZED', true);

        // Define plugin constants first
        self::define_constants();

        // Then load required files
        self::load_dependencies();

        // Register hooks but don't initialize components immediately
        self::register_hooks();
    }/**
     * Define plugin constants
     */
    private static function define_constants()
    {
        // Define plugin version
        if (!defined('HERITAGEPRESS_VERSION')) {
            define('HERITAGEPRESS_VERSION', self::VERSION);
        }

        // Define plugin file path
        if (!defined('HERITAGEPRESS_PLUGIN_FILE')) {
            define('HERITAGEPRESS_PLUGIN_FILE', dirname(dirname(dirname(__FILE__))) . '/heritagepress.php');
        }

        // These constants should be defined in the main plugin file
        // We only define them here if they haven't been defined already
        if (!defined('HERITAGEPRESS_PLUGIN_DIR')) {
            define('HERITAGEPRESS_PLUGIN_DIR', dirname(dirname(dirname(__FILE__))) . '/');
        }

        // Use WordPress core function directly
        if (!function_exists('plugin_dir_url')) {
            require_once ABSPATH . 'wp-includes/plugin.php';
        }

        if (!defined('HERITAGEPRESS_PLUGIN_URL')) {
            define('HERITAGEPRESS_PLUGIN_URL', plugin_dir_url(dirname(dirname(dirname(__FILE__))) . '/heritagepress.php'));
        }
    }

    /**
     * Load required dependencies
     */
    private static function load_dependencies()
    {
        // Load plugin files
        require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/bootstrap.php';
        require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-heritagepress-autoloader.php';

        // Register autoloader
        HeritagePress_Autoloader::register();
    }    /**
         * Register plugin hooks
         */
    private static function register_hooks()
    {
        // Activation/deactivation hooks - these need special WordPress functions
        if (!function_exists('register_activation_hook')) {
            require_once ABSPATH . 'wp-includes/plugin.php';
        }
        \register_activation_hook(HERITAGEPRESS_PLUGIN_FILE, [__CLASS__, 'activate']);
        \register_deactivation_hook(HERITAGEPRESS_PLUGIN_FILE, [__CLASS__, 'deactivate']);

        // Load text domain on init hook (proper timing for WordPress)
        WPHelper::addAction('init', [__CLASS__, 'load_textdomain']);

        // Initialize components on init hook to ensure proper timing
        WPHelper::addAction('init', [__CLASS__, 'initialize_components'], 5);

        // Suppress buffer compression warnings (server configuration issue)
        WPHelper::addAction('init', [__CLASS__, 'suppress_buffer_warnings'], 1);
    }

    /**
     * Suppress buffer compression warnings
     * These are server-level configuration warnings, not plugin errors
     */
    public static function suppress_buffer_warnings()
    {
        // Add custom error handler for buffer compression warnings
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            // Suppress zlib output compression warnings
            if (strpos($errstr, 'Failed to send buffer of zlib output compression') !== false) {
                return true; // Suppress this specific warning
            }

            // Let other errors be handled normally
            return false;
        }, E_NOTICE | E_WARNING);
    }    /**
         * Initialize plugin components
         */
    public static function initialize_components()
    {
        // Check if components have already been initialized
        if (self::$components_initialized) {
            return;
        }

        // Set flag to prevent duplicate initialization - do this first before any other operations
        self::$components_initialized = true;

        // Initialize AJAX handlers early for all contexts (admin and frontend AJAX requests)
        if (WPHelper::isAdmin() || (defined('DOING_AJAX') && DOING_AJAX)) {
            self::initialize_ajax_handlers();
        }

        // Static variable to ensure we only get Admin instance once per request
        static $admin_initialized = false;

        if (WPHelper::isAdmin() && !$admin_initialized) {
            $admin_initialized = true;
            $admin = HeritagePress\Admin\Admin::get_instance(HERITAGEPRESS_PLUGIN_DIR, HERITAGEPRESS_VERSION);
        }
    }

    /**
     * Initialize AJAX handlers early to ensure they're registered
     */
    private static function initialize_ajax_handlers()
    {
        // Import/Export AJAX handlers
        if (class_exists('HeritagePress\Admin\ImportExport\ImportHandler')) {
            $import_handler = new HeritagePress\Admin\ImportExport\ImportHandler();
        }

        if (class_exists('HeritagePress\Admin\ImportExport\ExportHandler')) {
            $export_handler = new HeritagePress\Admin\ImportExport\ExportHandler();
        }
    }

    /**
     * Load plugin textdomain for translations
     */
    public static function load_textdomain()
    {
        if (function_exists('load_plugin_textdomain')) {
            $lang_path = dirname(WPHelper::pluginBasename(HERITAGEPRESS_PLUGIN_FILE)) . '/languages';
            load_plugin_textdomain('heritagepress', false, $lang_path);
        }
    }

    /**
     * Plugin activation
     */
    public static function activate()
    {
        if (version_compare(WPHelper::getWpVersion(), '5.0', '<')) {
            WPHelper::deactivatePlugins(WPHelper::pluginBasename(HERITAGEPRESS_PLUGIN_FILE));
            wp_die('This plugin requires WordPress version 5.0 or higher');
        }

        // Create database tables
        require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/Database/Manager.php';
        $db_manager = new HeritagePress\Database\Manager(HERITAGEPRESS_PLUGIN_DIR, self::VERSION);
        $db_manager->install();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate()
    {
        WPHelper::flushRewriteRules();
    }
}
