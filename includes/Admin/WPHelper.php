<?php
namespace HeritagePress\Database;

/**
 * WordPress Functions Helper
 */
class WPHelper
{
    // ...existing code...

    /**
     * Get plugin URL
     *
     * @param string $plugin_file Plugin file path
     * @return string Plugin URL
     */
    public static function getPluginUrl($plugin_file)
    {
        return \plugin_dir_url($plugin_file);
    }

    /**
     * Get plugin path
     *
     * @param string $plugin_file Plugin file path
     * @return string Plugin path
     */
    public static function getPluginPath($plugin_file)
    {
        return \plugin_dir_path($plugin_file);
    }

    // ...existing code...
}