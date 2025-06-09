<?php
namespace HeritagePress\Database;

/**
 * WordPress Functions Helper
 */
class WPHelper
{
    /**
     * Get WordPress option
     *
     * @param string $option Option name
     * @param mixed  $default Default value
     * @return mixed Option value
     */
    public static function getOption($option, $default = false)
    {
        return \get_option($option, $default);
    }

    /**
     * Update WordPress option
     *
     * @param string $option Option name
     * @param mixed  $value Option value
     * @return bool True if updated
     */
    public static function updateOption($option, $value)
    {
        return \update_option($option, $value);
    }

    /**
     * Run dbDelta function
     *
     * @param string $sql SQL query
     * @return array Results
     */
    public static function dbDelta($sql)
    {
        if (!function_exists('dbDelta')) {
            require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        return \dbDelta($sql);
    }
}
