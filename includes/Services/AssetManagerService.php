<?php
/**
 * Asset Manager Service
 *
 * Centralized asset management for CSS/JS loading.
 * Provides dependency injection support and smart asset loading.
 *
 * @package HeritagePress
 * @subpackage Services
 * @since 1.0.0
 */

namespace HeritagePress\Services;

use HeritagePress\Core\ServiceContainer;

/**
 * Asset Manager Service Class
 */
class AssetManagerService
{
    /**
     * Service container
     *
     * @var ServiceContainer
     */
    private $container;

    /**
     * Plugin URL
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Registered assets
     *
     * @var array
     */
    private $assets = [];

    /**
     * Constructor
     *
     * @param ServiceContainer $container
     * @param string $plugin_url
     * @param string $version
     */
    public function __construct(ServiceContainer $container, $plugin_url, $version = '1.0.0')
    {
        $this->container = $container;
        $this->plugin_url = $plugin_url;
        $this->version = $version;

        $this->register_default_assets();
    }

    /**
     * Register default assets
     */
    private function register_default_assets()
    {
        // Core admin styles
        $this->register_asset('style', 'heritagepress-admin', [
            'url' => 'assets/css/admin.css',
            'deps' => [],
            'pages' => ['heritagepress']
        ]);        // Import/Export specific styles
        $this->register_asset('style', 'heritagepress-import-export', [
            'url' => 'assets/css/import-export.css',
            'deps' => ['heritagepress-admin'],
            'pages' => ['heritagepress-import-export']
        ]);

        // Core admin scripts
        $this->register_asset('script', 'heritagepress-admin', [
            'url' => 'assets/js/admin.js',
            'deps' => ['jquery'],
            'pages' => ['heritagepress']
        ]);        // Import/Export scripts
        $this->register_asset('script', 'heritagepress-import-export', [
            'url' => 'assets/js/import-export.js',
            'deps' => ['jquery', 'wp-util'],
            'pages' => ['heritagepress-import-export'],
            'localize' => [
                'var_name' => 'hp_vars',
                'data' => [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'hp_admin_url' => admin_url(),
                    'nonce' => wp_create_nonce('hp_admin_nonce'),
                    'hp_i18n' => [
                        'file_too_large' => __('File is too large. Maximum size is 50MB.', 'heritagepress'),
                        'invalid_file_type' => __('Invalid file type. Only .ged and .gedcom files are allowed.', 'heritagepress'),
                        'ajax_error' => __('An error occurred. Please try again.', 'heritagepress'),
                        'upload_failed' => __('Upload failed. Please try again.', 'heritagepress'),
                        'no_file' => __('Please select a GEDCOM file to upload.', 'heritagepress'),
                        'tree_name_required' => __('Please enter a name for the new tree.', 'heritagepress'),
                    ]
                ]
            ]
        ]);        // Date validation module
        $this->register_asset('script', 'heritagepress-date-validation', [
            'url' => 'assets/js/modules/date-validation.js',
            'deps' => ['jquery'],
            'pages' => ['heritagepress-import-export']
        ]);
    }

    /**
     * Register an asset
     *
     * @param string $type Asset type (style|script)
     * @param string $handle Asset handle
     * @param array $config Asset configuration
     */
    public function register_asset($type, $handle, $config)
    {
        $this->assets[$type][$handle] = array_merge([
            'url' => '',
            'deps' => [],
            'pages' => [],
            'localize' => null,
            'condition' => null
        ], $config);
    }    /**
         * Enqueue assets for current page
         *
         * @param string $hook Current admin page hook
         */
    public function enqueue_assets($hook)
    {
        $current_page = $this->get_current_page();

        // Enqueue styles
        $this->enqueue_assets_by_type('style', $current_page);

        // Enqueue scripts
        $this->enqueue_assets_by_type('script', $current_page);
    }

    /**
     * Enqueue assets by type
     *
     * @param string $type Asset type
     * @param string $current_page Current page slug
     */
    private function enqueue_assets_by_type($type, $current_page)
    {
        if (!isset($this->assets[$type])) {
            return;
        }

        foreach ($this->assets[$type] as $handle => $config) {
            if ($this->should_enqueue_asset($config, $current_page)) {
                $this->enqueue_single_asset($type, $handle, $config);
            }
        }
    }

    /**
     * Check if asset should be enqueued
     *
     * @param array $config Asset configuration
     * @param string $current_page Current page slug
     * @return bool
     */
    private function should_enqueue_asset($config, $current_page)
    {
        // Check page restriction
        if (!empty($config['pages']) && !$this->page_matches($config['pages'], $current_page)) {
            return false;
        }

        // Check custom condition
        if ($config['condition'] && !call_user_func($config['condition'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if current page matches asset pages
     *
     * @param array $asset_pages Asset page restrictions
     * @param string $current_page Current page slug
     * @return bool
     */
    private function page_matches($asset_pages, $current_page)
    {
        foreach ($asset_pages as $page) {
            if (strpos($current_page, $page) !== false) {
                return true;
            }
        }
        return false;
    }    /**
         * Enqueue single asset
         *
         * @param string $type Asset type
         * @param string $handle Asset handle
         * @param array $config Asset configuration
         */
    private function enqueue_single_asset($type, $handle, $config)
    {
        $url = $this->plugin_url . $config['url'];

        if ($type === 'style') {
            wp_enqueue_style($handle, $url, $config['deps'], $this->version);
        } elseif ($type === 'script') {
            wp_enqueue_script($handle, $url, $config['deps'], $this->version, true);

            // Handle script localization
            if ($config['localize']) {
                wp_localize_script(
                    $handle,
                    $config['localize']['var_name'],
                    $config['localize']['data']
                );
            }
        }
    }

    /**
     * Get current page slug
     *
     * @return string
     */
    private function get_current_page()
    {
        return isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    }

    /**
     * Add conditional asset
     *
     * @param string $type Asset type
     * @param string $handle Asset handle
     * @param array $config Asset configuration
     * @param callable $condition Condition function
     */
    public function add_conditional_asset($type, $handle, $config, $condition)
    {
        $config['condition'] = $condition;
        $this->register_asset($type, $handle, $config);
    }

    /**
     * Get registered assets
     *
     * @param string $type Optional asset type filter
     * @return array
     */
    public function get_assets($type = null)
    {
        return $type ? ($this->assets[$type] ?? []) : $this->assets;
    }
}
