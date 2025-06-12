<?php
/**
 * Asset Manager for HeritagePress
 *
 * Modernized asset manager using AssetManagerService for better organization.
 *
 * @package HeritagePress
 * @subpackage Admin
 * @since 1.0.0
 */

namespace HeritagePress\Admin;

use HeritagePress\Services\AssetManagerService;
use HeritagePress\Core\ServiceContainer;

/**
 * Asset Manager Class
 */
class AssetManager
{
    /**
     * Asset manager service
     *
     * @var AssetManagerService
     */
    private $asset_service;

    /**
     * Constructor
     * 
     * @param string $plugin_url Base URL for the plugin
     * @param ServiceContainer $container Optional service container
     */
    public function __construct($plugin_url, ServiceContainer $container = null)
    {
        $container = $container ?: new ServiceContainer();
        $this->asset_service = new AssetManagerService($container, $plugin_url, HERITAGEPRESS_VERSION);
    }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook The current admin page
     */
    public function enqueue_assets($hook)
    {
        // Only load on HeritagePress admin pages
        if (strpos($hook, 'heritagepress') === false) {
            return;
        }

        $this->asset_service->enqueue_assets($hook);
    }

    /**
     * Register additional asset
     *
     * @param string $type Asset type (style|script)
     * @param string $handle Asset handle
     * @param array $config Asset configuration
     */
    public function register_asset($type, $handle, $config)
    {
        $this->asset_service->register_asset($type, $handle, $config);
    }

    /**
     * Get asset service for advanced usage
     *
     * @return AssetManagerService
     */
    public function get_asset_service()
    {
        return $this->asset_service;
    }
}
