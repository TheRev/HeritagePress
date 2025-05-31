<?php
namespace HeritagePress\Tests\Mocks;

// Create function aliases in global namespace
if (!function_exists('current_time')) {
    function current_time($type, $gmt = false) {
        return MockWP::current_time($type, $gmt);
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return MockWP::plugin_dir_url($file);
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return MockWP::plugin_basename($file);
    }
}

if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group = '') {
        return MockWP::cache_get($key, $group);
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $value, $group = '', $expire = 0) {
        return MockWP::cache_set($key, $value, $group, $expire);
    }
}

class MockWP {    private static $actions = [];
    private static $filters = [];
    private static $cache = [];
    private static $cache_groups = [];
    private static $time_offset = 0;

    public static function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        if (!isset(self::$actions[$hook])) {
            self::$actions[$hook] = [];
        }
        self::$actions[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];
        return true;
    }

    public static function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        if (!isset(self::$filters[$hook])) {
            self::$filters[$hook] = [];
        }
        self::$filters[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];
        return true;
    }

    public static function plugin_dir_url($file) {
        return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
    }

    public static function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }

    public static function register_activation_hook($file, $callback) {
        self::add_action('activate_' . plugin_basename($file), $callback);
    }

    public static function register_deactivation_hook($file, $callback) {
        self::add_action('deactivate_' . plugin_basename($file), $callback);
    }

    public static function cache_get($key, $group = '', $force = false) {
        $cache_key = $group ? $group . '_' . $key : $key;
        return self::$cache[$cache_key] ?? false;
    }

    public static function cache_set($key, $data, $group = '', $expire = 0) {
        $cache_key = $group ? $group . '_' . $key : $key;
        self::$cache[$cache_key] = $data;
        if ($group && !in_array($group, self::$cache_groups)) {
            self::$cache_groups[] = $group;
        }
        return true;
    }

    public static function cache_delete($key, $group = '') {
        $cache_key = $group ? $group . '_' . $key : $key;
        unset(self::$cache[$cache_key]);
        return true;
    }

    public static function cache_delete_group($group) {
        foreach (self::$cache as $key => $value) {
            if (strpos($key, $group . '_') === 0) {
                unset(self::$cache[$key]);
            }
        }
        $index = array_search($group, self::$cache_groups);
        if ($index !== false) {
            unset(self::$cache_groups[$index]);
        }
        return true;
    }    /**
     * Mock current_time function
     * 
     * @param string $type Type of time to retrieve (mysql|timestamp)
     * @param bool $gmt Whether to use GMT timezone
     * @return string|int
     */
    public static function current_time($type, $gmt = false) {
        $time = time() + self::$time_offset;
        if ($type === 'mysql') {
            return date('Y-m-d H:i:s', $time);
        }
        return $time;
    }

    /**
     * Advance the mock time by specified seconds
     */
    public static function advance_time($seconds = 1) {
        self::$time_offset += $seconds;
    }

    public static function reset() {
        self::$actions = [];
        self::$filters = [];
        self::$cache = [];
        self::$cache_groups = [];
        self::$time_offset = 0;
    }
}
