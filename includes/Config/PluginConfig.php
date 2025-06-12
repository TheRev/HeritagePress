<?php
/**
 * Plugin Configuration Class
 *
 * Central configuration for the HeritagePress plugin.
 *
 * @package HeritagePress
 * @subpackage Config
 * @since 1.0.0
 */

namespace HeritagePress\Config;

/**
 * Class PluginConfig
 *
 * Defines core plugin configuration and constants.
 */
class PluginConfig
{
    /**
     * Plugin version
     */
    public const VERSION = '1.0.0';

    /**
     * Minimum WordPress version
     */
    public const MIN_WP_VERSION = '5.0';

    /**
     * Minimum PHP version
     */
    public const MIN_PHP_VERSION = '7.4';

    /**
     * Plugin text domain
     */
    public const TEXT_DOMAIN = 'heritagepress';

    /**
     * Database table prefix
     */
    public const DB_PREFIX = 'hp_';

    /**
     * Get plugin paths
     *
     * @return array
     */
    public static function getPaths(): array
    {
        return [
            'plugin_dir' => HERITAGEPRESS_PLUGIN_DIR,
            'plugin_url' => HERITAGEPRESS_PLUGIN_URL,
            'includes' => HERITAGEPRESS_PLUGIN_DIR . 'includes/',
            'templates' => HERITAGEPRESS_PLUGIN_DIR . 'templates/',
            'assets' => HERITAGEPRESS_PLUGIN_URL . 'assets/',
            'languages' => HERITAGEPRESS_PLUGIN_DIR . 'languages/'
        ];
    }

    /**
     * Get database table names
     *
     * @return array
     */
    public static function getTableNames(): array
    {
        global $wpdb;

        return [
            'people' => $wpdb->prefix . self::DB_PREFIX . 'people',
            'families' => $wpdb->prefix . self::DB_PREFIX . 'families',
            'children' => $wpdb->prefix . self::DB_PREFIX . 'children',
            'events' => $wpdb->prefix . self::DB_PREFIX . 'events',
            'eventtypes' => $wpdb->prefix . self::DB_PREFIX . 'eventtypes',
            'places' => $wpdb->prefix . self::DB_PREFIX . 'places',
            'sources' => $wpdb->prefix . self::DB_PREFIX . 'sources',
            'citations' => $wpdb->prefix . self::DB_PREFIX . 'citations',
            'repositories' => $wpdb->prefix . self::DB_PREFIX . 'repositories',
            'media' => $wpdb->prefix . self::DB_PREFIX . 'media',
            'medialinks' => $wpdb->prefix . self::DB_PREFIX . 'medialinks',
            'notes' => $wpdb->prefix . self::DB_PREFIX . 'notes',
            'notelinks' => $wpdb->prefix . self::DB_PREFIX . 'notelinks',
            'trees' => $wpdb->prefix . self::DB_PREFIX . 'trees',
            'xnotes' => $wpdb->prefix . self::DB_PREFIX . 'xnotes',
            'addresses' => $wpdb->prefix . self::DB_PREFIX . 'addresses',
            'associations' => $wpdb->prefix . self::DB_PREFIX . 'associations',
            'ldsords' => $wpdb->prefix . self::DB_PREFIX . 'ldsords',
            'albums' => $wpdb->prefix . self::DB_PREFIX . 'albums',
            'albumlinks' => $wpdb->prefix . self::DB_PREFIX . 'albumlinks',
            'dnamatches' => $wpdb->prefix . self::DB_PREFIX . 'dnamatches',
            'dnatests' => $wpdb->prefix . self::DB_PREFIX . 'dnatests',
            'templates' => $wpdb->prefix . self::DB_PREFIX . 'templates',
            'tempassignments' => $wpdb->prefix . self::DB_PREFIX . 'tempassignments',
            'tempcitations' => $wpdb->prefix . self::DB_PREFIX . 'tempcitations',
            'cemeteries' => $wpdb->prefix . self::DB_PREFIX . 'cemeteries',
            'headstones' => $wpdb->prefix . self::DB_PREFIX . 'headstones',
            'headstonelinks' => $wpdb->prefix . self::DB_PREFIX . 'headstonelinks',
            'stylesheets' => $wpdb->prefix . self::DB_PREFIX . 'stylesheets',
            'logs' => $wpdb->prefix . self::DB_PREFIX . 'logs',
            'users' => $wpdb->prefix . self::DB_PREFIX . 'users',
            'config' => $wpdb->prefix . self::DB_PREFIX . 'config',
            'adminnotes' => $wpdb->prefix . self::DB_PREFIX . 'adminnotes',
            'folders' => $wpdb->prefix . self::DB_PREFIX . 'folders',
            'mostwanted' => $wpdb->prefix . self::DB_PREFIX . 'mostwanted',
            'exports' => $wpdb->prefix . self::DB_PREFIX . 'exports',
            'mod_monitor' => $wpdb->prefix . self::DB_PREFIX . 'mod_monitor',
            'dsnames' => $wpdb->prefix . self::DB_PREFIX . 'dsnames',
            'dssurnames' => $wpdb->prefix . self::DB_PREFIX . 'dssurnames',
            'dsplaces' => $wpdb->prefix . self::DB_PREFIX . 'dsplaces'
        ];
    }

    /**
     * Get asset configuration
     *
     * @return array
     */
    public static function getAssets(): array
    {
        return [
            'admin_css' => [
                'handle' => 'heritagepress-admin',
                'src' => self::getPaths()['assets'] . 'css/admin.css',
                'deps' => [],
                'ver' => self::VERSION
            ],
            'admin_js' => [
                'handle' => 'heritagepress-admin',
                'src' => self::getPaths()['assets'] . 'js/admin.js',
                'deps' => ['jquery'],
                'ver' => self::VERSION
            ],
            'public_css' => [
                'handle' => 'heritagepress-public',
                'src' => self::getPaths()['assets'] . 'css/public.css',
                'deps' => [],
                'ver' => self::VERSION
            ],
            'public_js' => [
                'handle' => 'heritagepress-public',
                'src' => self::getPaths()['assets'] . 'js/public.js',
                'deps' => ['jquery'],
                'ver' => self::VERSION
            ]
        ];
    }

    /**
     * Get default plugin options
     *
     * @return array
     */
    public static function getDefaultOptions(): array
    {
        return [
            'version' => self::VERSION,
            'db_version' => '1.0.0',
            'installed' => false,
            'activated' => false,
            'enable_logging' => true,
            'log_level' => 'info',
            'cache_enabled' => true,
            'cache_duration' => 3600,
            'max_upload_size' => 50 * 1024 * 1024, // 50MB
            'allowed_file_types' => ['ged', 'gedcom'],
            'date_format' => 'Y-m-d',
            'privacy_default' => 'public'
        ];
    }
}
