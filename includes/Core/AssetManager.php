<?php
/**
 * Asset Manager Class
 *
 * Centralized management of CSS and JavaScript assets for HeritagePress.
 *
 * @package HeritagePress
 * @subpackage Core
 * @since 1.0.0
 */

namespace HeritagePress\Core;

use HeritagePress\Config\PluginConfig;

/**
 * Asset Manager Class
 */
class AssetManager
{
    /**
     * Registered assets
     *
     * @var array
     */
    private $assets = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->registerDefaultAssets();
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);
    }

    /**
     * Register default plugin assets
     */
    private function registerDefaultAssets()
    {
        $assets = PluginConfig::getAssets();

        foreach ($assets as $type => $config) {
            $this->registerAsset($type, $config);
        }
    }

    /**
     * Register an asset
     *
     * @param string $handle Asset handle
     * @param array $config Asset configuration
     */
    public function registerAsset($handle, $config)
    {
        $this->assets[$handle] = array_merge([
            'type' => 'css', // css or js
            'handle' => $handle,
            'src' => '',
            'deps' => [],
            'ver' => PluginConfig::VERSION,
            'media' => 'all', // for CSS
            'in_footer' => true,  // for JS
            'localize' => null,  // for JS localization
            'condition' => null   // callback to determine if should load
        ], $config);
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook_suffix Current admin page hook suffix
     */
    public function enqueueAdminAssets($hook_suffix)
    {
        // Only load on HeritagePress admin pages
        if (strpos($hook_suffix, 'heritagepress') === false) {
            return;
        }

        foreach ($this->assets as $handle => $config) {
            if (strpos($handle, 'admin') === false) {
                continue;
            }

            if (!$this->shouldLoadAsset($config, $hook_suffix)) {
                continue;
            }

            $this->enqueueAsset($config);
        }
    }

    /**
     * Enqueue public assets
     */
    public function enqueuePublicAssets()
    {
        foreach ($this->assets as $handle => $config) {
            if (strpos($handle, 'public') === false) {
                continue;
            }

            if (!$this->shouldLoadAsset($config)) {
                continue;
            }

            $this->enqueueAsset($config);
        }
    }

    /**
     * Enqueue a specific asset
     *
     * @param array $config Asset configuration
     */
    private function enqueueAsset($config)
    {
        if ($config['type'] === 'css') {
            wp_enqueue_style(
                $config['handle'],
                $config['src'],
                $config['deps'],
                $config['ver'],
                $config['media']
            );
        } elseif ($config['type'] === 'js') {
            wp_enqueue_script(
                $config['handle'],
                $config['src'],
                $config['deps'],
                $config['ver'],
                $config['in_footer']
            );

            // Handle script localization
            if (!empty($config['localize'])) {
                wp_localize_script(
                    $config['handle'],
                    $config['localize']['object_name'],
                    $config['localize']['data']
                );
            }
        }
    }

    /**
     * Check if asset should be loaded
     *
     * @param array $config Asset configuration
     * @param string $context Current context (hook_suffix for admin)
     * @return bool
     */
    private function shouldLoadAsset($config, $context = null)
    {
        if (empty($config['condition'])) {
            return true;
        }

        if (is_callable($config['condition'])) {
            return call_user_func($config['condition'], $context);
        }

        return true;
    }

    /**
     * Register Trees section assets
     */
    public function registerTreesAssets()
    {
        $this->registerAsset('heritagepress-trees-css', [
            'type' => 'css',
            'src' => PluginConfig::getPaths()['assets'] . 'css/admin-trees.css',
            'deps' => ['heritagepress-admin'],
            'condition' => function ($hook) {
                return strpos($hook, 'heritagepress-trees') !== false;
            }
        ]);

        $this->registerAsset('heritagepress-trees-js', [
            'type' => 'js',
            'src' => PluginConfig::getPaths()['assets'] . 'js/admin-trees.js',
            'deps' => ['jquery', 'heritagepress-admin'],
            'condition' => function ($hook) {
                return strpos($hook, 'heritagepress-trees') !== false;
            },
            'localize' => [
                'object_name' => 'heritagepress_trees',
                'data' => [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('heritagepress_trees_nonce'),
                    'messages' => [
                        'confirm_delete' => __('Are you sure you want to delete this tree?', 'heritagepress'),
                        'saving' => __('Saving...', 'heritagepress'),
                        'saved' => __('Saved!', 'heritagepress'),
                        'error' => __('An error occurred. Please try again.', 'heritagepress')
                    ]
                ]
            ]
        ]);
    }

    /**
     * Register Import/Export section assets
     */
    public function registerImportExportAssets()
    {
        $this->registerAsset('heritagepress-import-export-css', [
            'type' => 'css',
            'src' => PluginConfig::getPaths()['assets'] . 'css/admin-import-export.css',
            'deps' => ['heritagepress-admin'],
            'condition' => function ($hook) {
                return strpos($hook, 'heritagepress-import-export') !== false;
            }
        ]);

        $this->registerAsset('heritagepress-import-export-js', [
            'type' => 'js',
            'src' => PluginConfig::getPaths()['assets'] . 'js/admin-import-export.js',
            'deps' => ['jquery', 'heritagepress-admin'],
            'condition' => function ($hook) {
                return strpos($hook, 'heritagepress-import-export') !== false;
            },
            'localize' => [
                'object_name' => 'heritagepress_import_export',
                'data' => [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('heritagepress_import_export_nonce'),
                    'max_size' => wp_max_upload_size(),
                    'messages' => [
                        'uploading' => __('Uploading file...', 'heritagepress'),
                        'processing' => __('Processing GEDCOM file...', 'heritagepress'),
                        'importing' => __('Importing data...', 'heritagepress'),
                        'complete' => __('Import completed successfully!', 'heritagepress'),
                        'error' => __('An error occurred during import.', 'heritagepress'),
                        'invalid_file' => __('Please select a valid GEDCOM file.', 'heritagepress')
                    ]
                ]
            ]
        ]);
    }

    /**
     * Get all registered assets
     *
     * @return array
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * Remove an asset
     *
     * @param string $handle Asset handle
     */
    public function removeAsset($handle)
    {
        unset($this->assets[$handle]);
    }
}
