<?php
namespace HeritagePress\Models\Traits;

trait HasCache {
    /**
     * Whether caching is enabled for the model
     */
    protected static $cacheEnabled = true;

    /**
     * Cache expiration time in seconds (default 1 hour)
     */
    protected static $cacheExpiration = 3600;

    /**
     * Get a cached value
     */
    protected function getFromCache($key) {
        if (!static::$cacheEnabled) {
            return null;
        }

        $cached = wp_cache_get($this->getCacheKey($key), $this->getCacheGroup());
        return $cached === false ? null : $cached;
    }

    /**
     * Store a value in cache
     */
    protected function storeInCache($key, $value) {
        if (!static::$cacheEnabled) {
            return;
        }

        wp_cache_set(
            $this->getCacheKey($key),
            $value,
            $this->getCacheGroup(),
            static::$cacheExpiration
        );
    }

    /**
     * Delete a cached value
     */
    protected function deleteFromCache($key) {
        if (!static::$cacheEnabled) {
            return;
        }

        wp_cache_delete($this->getCacheKey($key), $this->getCacheGroup());
    }

    /**
     * Flush all cache for this model
     */
    public static function flushCache() {
        wp_cache_delete_group(static::class);
    }

    /**
     * Enable caching for this model
     */
    public static function enableCache() {
        static::$cacheEnabled = true;
    }

    /**
     * Disable caching for this model
     */
    public static function disableCache() {
        static::$cacheEnabled = false;
    }

    /**
     * Set cache expiration time
     */
    public static function setCacheExpiration($seconds) {
        static::$cacheExpiration = $seconds;
    }

    /**
     * Get cache key for a value
     */
    protected function getCacheKey($key) {
        return sprintf('%s:%s:%s', static::class, $this->id ?? 'null', $key);
    }

    /**
     * Get cache group for this model
     */
    protected function getCacheGroup() {
        return static::class;
    }
}
