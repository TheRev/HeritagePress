<?php
namespace HeritagePress\Admin;

/**
 * Handles asset (scripts/styles) management for the HeritagePress plugin
 */
class AssetManager
{
    /** @var string Plugin URL */
    private $plugin_url;

    /**
     * Constructor
     * 
     * @param string $plugin_url Base URL for the plugin
     */
    public function __construct($plugin_url)
    {
        $this->plugin_url = $plugin_url;
    }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook The current admin page
     */
    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'heritagepress') === false) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();
    }

    /**
     * Enqueue admin styles
     */
    private function enqueue_styles()
    {
        wp_enqueue_style(
            'heritagepress-admin',
            $this->plugin_url . 'assets/css/admin.css',
            [],
            HERITAGEPRESS_VERSION
        );

        // Check if we're on the import/export page
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'heritagepress-importexport') !== false) {
            wp_enqueue_style(
                'heritagepress-import-export',
                $this->plugin_url . 'assets/css/import-export.css',
                [],
                HERITAGEPRESS_VERSION
            );
        }
    }

    /**
     * Enqueue admin scripts
     */
    private function enqueue_scripts()
    {
        wp_enqueue_script(
            'heritagepress-admin',
            $this->plugin_url . 'assets/js/admin.js',
            ['jquery', 'wp-util'],
            HERITAGEPRESS_VERSION,
            true
        );

        wp_localize_script('heritagepress-admin', 'HeritagePress', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('heritagepress_ajax_nonce')
        ]);
    }
}
