<?php
/**
 * Menu Configuration Class
 *
 * Centralizes all menu configuration for the HeritagePress plugin.
 *
 * @package HeritagePress
 * @subpackage Config
 * @since 1.0.0
 */

namespace HeritagePress\Config;

/**
 * Class MenuConfig
 *
 * Defines the menu structure and configuration for the HeritagePress admin interface.
 */
class MenuConfig
{
    /**
     * Get the main menu configuration
     *
     * @return array
     */
    public static function getMainMenu(): array
    {
        return [
            'page_title' => __('HeritagePress', 'heritagepress'),
            'menu_title' => __('HeritagePress', 'heritagepress'),
            'capability' => 'manage_options',
            'menu_slug' => 'heritagepress',
            'function' => null,
            'icon_url' => 'dashicons-networking',
            'position' => 30
        ];
    }    /**
         * Get the submenu configuration
         *
         * @return array
         */
    public static function getSubmenus(): array
    {
        return [
            'trees' => [
                'parent_slug' => 'heritagepress',
                'page_title' => __('Trees', 'heritagepress'),
                'menu_title' => __('Trees', 'heritagepress'),
                'capability' => 'manage_options',
                'menu_slug' => 'heritagepress-trees',
                'manager_class' => 'TreesManager',
                'order' => 10
            ],
            'individuals' => [
                'parent_slug' => 'heritagepress',
                'page_title' => __('Individuals', 'heritagepress'),
                'menu_title' => __('Individuals', 'heritagepress'),
                'capability' => 'manage_options',
                'menu_slug' => 'heritagepress-individuals',
                'manager_class' => 'IndividualsManager',
                'order' => 20
            ],
            'import_export' => [
                'parent_slug' => 'heritagepress',
                'page_title' => __('Import/Export', 'heritagepress'),
                'menu_title' => __('Import/Export', 'heritagepress'),
                'capability' => 'manage_options',
                'menu_slug' => 'heritagepress-import-export',
                'manager_class' => 'ImportExportManager',
                'order' => 30
            ],
            'tools' => [
                'parent_slug' => 'heritagepress',
                'page_title' => __('Tools', 'heritagepress'),
                'menu_title' => __('Tools', 'heritagepress'),
                'capability' => 'manage_options',
                'menu_slug' => 'heritagepress-tools',
                'manager_class' => 'TableManager',
                'order' => 40
            ]
        ];
    }

    /**
     * Get ordered submenus
     *
     * @return array Submenus sorted by order
     */
    public static function getOrderedSubmenus(): array
    {
        $submenus = self::getSubmenus();

        // Sort by order
        uasort($submenus, function ($a, $b) {
            return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
        });

        return $submenus;
    }

    /**
     * Get manager class for a given menu slug
     *
     * @param string $menu_slug
     * @return string|null
     */
    public static function getManagerForSlug(string $menu_slug): ?string
    {
        $submenus = self::getSubmenus();

        foreach ($submenus as $submenu) {
            if ($submenu['menu_slug'] === $menu_slug) {
                return $submenu['manager_class'] ?? null;
            }
        }

        return null;
    }

    /**
     * Get capabilities required for each menu item
     *
     * @return array
     */
    public static function getCapabilities(): array
    {
        return [
            'heritagepress' => 'manage_options',
            'heritagepress-trees' => 'manage_options',
            'heritagepress-import-export' => 'manage_options',
            'heritagepress-people' => 'manage_options',
            'heritagepress-families' => 'manage_options',
            'heritagepress-places' => 'manage_options',
            'heritagepress-sources' => 'manage_options',
            'heritagepress-media' => 'manage_options',
            'heritagepress-reports' => 'manage_options',
            'heritagepress-settings' => 'manage_options'
        ];
    }
}
