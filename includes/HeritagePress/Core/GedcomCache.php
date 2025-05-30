<?php
namespace HeritagePress\Core;

class GedcomCache {
    private $cache_dir;
    private $cache_time = 3600; // 1 hour default

    public function __construct() {
        $this->cache_dir = HERITAGE_PRESS_PLUGIN_DIR . 'cache/gedcom/';
        $this->ensureCacheDirectory();
    }

    /**
     * Get cached GEDCOM data
     */
    public function get($key) {
        $cache_file = $this->getCacheFile($key);
        
        if (!file_exists($cache_file)) {
            return null;
        }

        // Check if cache is expired
        if (time() - filemtime($cache_file) > $this->cache_time) {
            unlink($cache_file);
            return null;
        }

        return json_decode(file_get_contents($cache_file), true);
    }

    /**
     * Store GEDCOM data in cache
     */
    public function set($key, $data) {
        $cache_file = $this->getCacheFile($key);
        return file_put_contents($cache_file, json_encode($data));
    }

    /**
     * Clear cached data
     */
    public function clear($key = null) {
        if ($key) {
            $cache_file = $this->getCacheFile($key);
            if (file_exists($cache_file)) {
                unlink($cache_file);
            }
        } else {
            array_map('unlink', glob($this->cache_dir . '*'));
        }
    }

    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        return $this->cache_dir . md5($key) . '.cache';
    }

    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory() {
        if (!file_exists($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
}
