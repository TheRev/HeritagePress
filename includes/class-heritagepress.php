<?php
/**
 * Main plugin class
 */

use HeritagePress\Database\WPHelper;

class HeritagePress
{
    /**
     * Plugin version
     */
    const VERSION = '1.0.0';

    /**
     * Flag to track whether components have been initialized
     */
    private static $components_initialized = false;

    /**
     * Plugin initialization
     */
    public static function init()
    {
        // Add debug logging with backtrace
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($backtrace[1]['file']) ? $backtrace[1]['file'] : 'unknown file';
        $line = isset($backtrace[1]['line']) ? $backtrace[1]['line'] : 'unknown';
        error_log('HeritagePress::init() called from: ' . $caller . ' line ' . $line);

        // Define plugin constants first
        self::define_constants();

        // Then load required files
        self::load_dependencies();

        // Initialize components directly
        self::initialize_components();

        // Register hooks (this will now skip the init hook registration since components are already initialized)
        self::register_hooks();
    }

    /**
     * Define plugin constants
     */
    private static function define_constants()
    {
        define('HERITAGEPRESS_VERSION', self::VERSION);
        define('HERITAGEPRESS_PLUGIN_FILE', dirname(dirname(__FILE__)) . '/heritagepress.php');
        define('HERITAGEPRESS_PLUGIN_DIR', dirname(dirname(__FILE__)) . '/');
        // Use WordPress core function directly
        if (!function_exists('plugin_dir_url')) {
            require_once ABSPATH . 'wp-includes/plugin.php';
        }
        define('HERITAGEPRESS_PLUGIN_URL', plugin_dir_url(dirname(dirname(__FILE__)) . '/heritagepress.php'));
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
    }

    /**
     * Register plugin hooks
     */
    private static function register_hooks()
    {
        // Activation/deactivation hooks
        WPHelper::addHook('activate_' . WPHelper::pluginBasename(HERITAGEPRESS_PLUGIN_FILE), [__CLASS__, 'activate']);
        WPHelper::addHook('deactivate_' . WPHelper::pluginBasename(HERITAGEPRESS_PLUGIN_FILE), [__CLASS__, 'deactivate']);

        // Only add the init hook if we haven't initialized components directly
        if (!self::$components_initialized) {
            // Init hook - lower priority ensures it runs after direct initialization if that happens
            WPHelper::addAction('init', [__CLASS__, 'initialize_components'], 5);
        }
    }

    /**
     * Initialize plugin components
     */
    public static function initialize_components()
    {
        // Check if components have already been initialized
        if (self::$components_initialized) {
            error_log('HeritagePress: initialize_components() called again but skipped (already initialized)');
            return;
        }

        // Add debug logging
        error_log('HeritagePress: initialize_components() called - initializing components');

        // Set flag to prevent duplicate initialization
        self::$components_initialized = true;
        if (WPHelper::isAdmin()) {
            error_log('HeritagePress: Getting Admin instance (singleton)');
            $admin = HeritagePress\Admin\Admin::get_instance(HERITAGEPRESS_PLUGIN_DIR, HERITAGEPRESS_VERSION);
        }

        // Load text domain
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
