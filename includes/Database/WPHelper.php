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

    /**
     * Add action hook
     *
     * @param string   $hook          Hook name
     * @param callable $callback      Function to execute
     * @param int      $priority      Priority of execution
     * @param int      $accepted_args Number of arguments accepted
     */
    public static function addAction($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        \add_action($hook, $callback, $priority, $accepted_args);
    }

    /**
     * Sanitize text field
     *
     * @param string $text Text to sanitize
     * @return string Sanitized text
     */
    public static function sanitizeTextField($text)
    {
        return \sanitize_text_field($text);
    }

    /**
     * Sanitize textarea field
     *
     * @param string $text Text to sanitize
     * @return string Sanitized text
     */
    public static function sanitizeTextareaField($text)
    {
        return \sanitize_textarea_field($text);
    }

    /**
     * Verify nonce and check admin referrer
     *
     * @param string $nonce     Nonce value
     * @param string $action    Action name
     * @return bool True if verified
     */
    public static function verifyNonce($nonce, $action = -1)
    {
        return \wp_verify_nonce($nonce, $action);
    }

    /**
     * Create nonce
     *
     * @param string $action Action name
     * @return string Nonce value
     */
    public static function createNonce($action = -1)
    {
        return \wp_create_nonce($action);
    }

    /**
     * Safe redirect to a URL
     *
     * @param string $location URL to redirect to
     * @param bool   $exit     Whether to exit after redirect
     */    /**
          * Safely encode a URL
          * 
          * @param string $url URL to encode
          * @return string Encoded URL
          */
    private static function encodeUrl($url)
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Safe redirect to a URL
     *
     * @param string $location URL to redirect to
     * @param bool   $exit     Whether to exit after redirect
     */
    public static function safeRedirect($location, $exit = true)
    {
        $safe_location = self::encodeUrl($location);
        if (headers_sent()) {
            echo "<script>window.location.href='" . $safe_location . "';</script>";
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url=' . $safe_location . '">';
            echo '</noscript>';
        } else {
            if (function_exists('wp_redirect')) {
                \wp_redirect($location, 302);
            } else {
                header('Location: ' . $location, true, 302);
            }
        }
        if ($exit) {
            exit;
        }
    }

    /**
     * Get admin URL
     *
     * @param string $path   Path relative to admin URL
     * @param array  $params Query parameters
     * @return string Admin URL
     */
    public static function adminUrl($path = '', $params = [])
    {
        $url = \admin_url($path);
        if (!empty($params)) {
            $query = http_build_query($params);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
        }
        return $url;
    }

    /**
     * Get current user ID
     *
     * @return int Current user ID
     */
    public static function getCurrentUserId()
    {
        return \get_current_user_id();
    }

    /**
     * Get plugin URL
     *
     * @param string $plugin_file Plugin file path
     * @return string Plugin URL
     */
    public static function getPluginUrl($plugin_file)
    {
        if (!function_exists('plugin_dir_url')) {
            require_once \ABSPATH . 'wp-includes/plugin.php';
        }
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
        if (!function_exists('plugin_dir_path')) {
            require_once \ABSPATH . 'wp-includes/plugin.php';
        }
        return \plugin_dir_path($plugin_file);
    }

    /**
     * Get the WordPress version
     *
     * @return string WordPress version
     */
    public static function getWpVersion()
    {
        if (!function_exists('get_bloginfo')) {
            require_once \ABSPATH . 'wp-includes/general-template.php';
        }
        return \get_bloginfo('version');
    }

    /**
     * Deactivate plugins
     *
     * @param string|array $plugins Single plugin or list of plugins to deactivate
     */
    public static function deactivatePlugins($plugins)
    {
        if (!function_exists('deactivate_plugins')) {
            require_once \ABSPATH . 'wp-admin/includes/plugin.php';
        }
        \deactivate_plugins($plugins);
    }

    /**
     * Flush rewrite rules
     */
    public static function flushRewriteRules()
    {
        if (!function_exists('flush_rewrite_rules')) {
            require_once \ABSPATH . 'wp-includes/rewrite.php';
        }
        \flush_rewrite_rules();
    }

    /**
     * Check if current request is for an admin page
     *
     * @return bool True if admin page
     */
    public static function isAdmin()
    {
        if (!function_exists('is_admin')) {
            require_once \ABSPATH . 'wp-includes/load.php';
        }
        return \is_admin();
    }

    /**
     * Get plugin basename
     *
     * @param string $file Plugin file
     * @return string Plugin basename
     */
    public static function pluginBasename($file)
    {
        if (!function_exists('plugin_basename')) {
            require_once \ABSPATH . 'wp-includes/plugin.php';
        }
        return \plugin_basename($file);
    }

    /**
     * Register hooks
     *
     * @param string   $hook Hook name
     * @param callable $callback Function to execute
     */
    public static function addHook($hook, $callback)
    {
        if (!function_exists('add_action')) {
            require_once \ABSPATH . 'wp-includes/plugin.php';
        }
        \add_action($hook, $callback);
    }
}
