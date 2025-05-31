<?php

// Mock WordPress global functions for testing

use HeritagePress\Tests\Mocks\MockWP;

if (!function_exists('current_time')) {
    function current_time($type, $gmt = false) {
        return MockWP::current_time($type, $gmt);
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        return MockWP::add_action($hook, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        return MockWP::add_filter($hook, $callback, $priority, $accepted_args);
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

if (!function_exists('esc_sql')) {
    function esc_sql($data) {
        global $wpdb;
        return $wpdb->escape($data);
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = '') {
        if (is_object($args)) {
            $r = get_object_vars($args);
        } elseif (is_array($args)) {
            $r =& $args;
        } else {
            parse_str($args, $r);
        }

        if (is_array($defaults)) {
            return array_merge($defaults, $r);
        }
        return $r;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1; // Return a test user ID
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}
