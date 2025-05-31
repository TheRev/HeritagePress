<?php
/**
 * Base Model Class
 *
 * This abstract class provides common functionality for all models in the genealogy plugin.
 * It implements basic data storage, retrieval, and database operations.
 *
 * @package HeritagePress
 * @subpackage                elseif ($rule === 'date' && !empty($value)) {
                    if (!strtotime($value)) {
                        $this->errors[$field][] = "$field must be a valid date";
                    }
                } elseif (strpos($rule, 'max:') === 0) {
                    $max = substr($rule, 4);
                    if ($value !== null && strlen((string)$value) > $max) {
                        $this->errors[$field][] = "$field must not exceed $max characters";
                    }
                } elseif ($rule === 'numeric' && $value !== null) {
                    if (!is_numeric($value)) {
                        $this->errors[$field][] = "$field must be a number";
                    }
                } elseif (strpos($rule, 'min:') === 0) {
                    $min = substr($rule, 4);
                    if ($value !== null && $value < $min) {
                        $this->errors[$field][] = "$field must be at least $min";
                    }
                } elseif (strpos($rule, 'in:') === 0) {
                    $allowed = explode(',', substr($rule, 3));
                    if ($value !== null && !in_array($value, $allowed)) {
                        $this->errors[$field][] = "$field must be one of: " . implode(', ', $allowed);
                    }
                }*/

namespace HeritagePress\Models;

use HeritagePress\Database\QueryBuilder;

/**
 * Class Model
 */
abstract class Model {
    /**
     * The database table name
     */
    protected $table;

    /**
     * The primary key field name
     */
    protected $primary_key = 'id';

    /**
     * Cache TTL in seconds (default 1 hour)
     */
    protected static $cacheTTL = 3600;

    /**
     * Cache prefix for all models
     */
    protected static $cachePrefix = 'heritage_press_';    protected $data = [];
    protected $fillable = [];
    protected $rules = [];
    protected $errors = [];

    private static $observers = [];
    private static $cache = [];
    protected static $cacheEnabled = true;
    protected static $cacheTags = [];

    protected $relationships = [];

    public function __construct(array $data = []) {
        $this->fill($data);
        // Initialize cache tags with model name
        static::$cacheTags[] = static::class;
    }

    public function fill(array $data) {
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                $this->data[$field] = $data[$field];
            }
        }
    }

    public function __get($name) {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value) {
        if (in_array($name, $this->fillable)) {
            $this->data[$name] = $value;
        }
    }

    public function toArray() {
        return $this->data;
    }

    /**
     * Register an observer for model events
     * 
     * @param ModelObserver $observer The observer instance
     */
    public static function observe(ModelObserver $observer) {
        $class = get_called_class();
        if (!isset(self::$observers[$class])) {
            self::$observers[$class] = [];
        }
        self::$observers[$class][] = $observer;
    }

    /**
     * Fire an event to all observers
     * 
     * @param string $event Event name
     */
    protected function fireEvent(string $event) {
        $class = get_class($this);
        if (!isset(self::$observers[$class])) {
            return;
        }

        foreach (self::$observers[$class] as $observer) {
            $observer->$event($this);
        }
    }

    /**
     * Enable or disable caching for this model
     */
    public static function enableCache(bool $enabled = true): void {
        static::$cacheEnabled = $enabled;
    }

    /**
     * Set cache TTL for this model
     */
    public static function setCacheTTL(int $seconds): void {
        static::$cacheTTL = $seconds;
    }

    /**
     * Set cache prefix for this model
     */
    public static function setCachePrefix(string $prefix): void {
        static::$cachePrefix = $prefix;
    }

    /**
     * Add a cache tag for this model
     */
    public static function addCacheTag(string $tag): void {
        if (!in_array($tag, static::$cacheTags)) {
            static::$cacheTags[] = $tag;
        }
    }

    /**
     * Get cache tags for this model
     */
    protected function getCacheTags(): array {
        return static::$cacheTags;
    }

    /**
     * Get cache key for a model instance
     */
    protected static function getCacheKey($model, $id) {
        return sprintf(
            'heritage_press_%s_%s',
            $model->table,
            $id
        );
    }

    protected function getTableName()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'heritage_press_' . $this->table;
        return $table;
    }

    /**
     * Cache the model data
     */
    protected function cache($id, $data) {
        wp_cache_set(
            self::getCacheKey($this, $id),
            $data,
            'heritage_press',
            static::$cacheTTL
        );
    }

    /**
     * Get cached model data
     */
    protected function getCached($id) {
        return wp_cache_get(
            self::getCacheKey($this, $id),
            'heritage_press'
        );
    }

    /**
     * Cache model data with relationships
     */
    protected function cacheModel(): void {
        if (!static::$cacheEnabled || !isset($this->data['id'])) {
            return;
        }

        $data = [
            'attributes' => $this->data,
            'relationships' => $this->relationships
        ];

        wp_cache_set_multiple([            $this->getCacheKey() => $data
        ], 'heritage_press', static::$cacheTTL);

        // Cache relationship data separately
        foreach ($this->relationships as $name => $related) {
            if (is_object($related)) {
                $related->cacheModel();
            } elseif (is_array($related)) {
                foreach ($related as $model) {
                    if (is_object($model)) {
                        $model->cacheModel();
                    }
                }
            }
        }
    }

    /**
     * Get model from cache including relationships
     */
    protected static function getFromCache($id) {
        if (!static::$cacheEnabled) {
            return null;
        }

        $class = get_called_class();
        $model = new $class();
        $data = wp_cache_get(            sprintf('%s%s_%s', static::$cachePrefix, $model->table, $id),
            'heritage_press'
        );

        if ($data === false) {
            return null;
        }

        $model->fill($data['attributes'] ?? []);
        $model->relationships = $data['relationships'] ?? [];
        return $model;
    }

    /**
     * Flush cache for all models with given tags
     */
    public static function flushCacheByTags(array $tags): void {
        if (!static::$cacheEnabled) {
            return;
        }

        foreach ($tags as $tag) {
            wp_cache_delete_group($tag);
        }
    }

    /**
     * Flush all cache for this model
     */
    public static function flushCache(): void {
        if (!static::$cacheEnabled) {
            return;
        }

        wp_cache_delete_group(static::class);
    }

    /**
     * Cache query results
     */
    protected function cacheQueryResults(string $query, array $results): void {
        if (!static::$cacheEnabled) {
            return;
        }

        $key = sprintf('%squery_%s', static::$cachePrefix, md5($query));
        wp_cache_set($key, $results, 'heritage_press', static::$cacheTTL);
    }

    /**
     * Get cached query results
     */
    protected function getCachedQueryResults(string $query) {
        if (!static::$cacheEnabled) {
            return null;
        }

        $key = sprintf('%squery_%s', static::$cachePrefix, md5($query));
        return wp_cache_get($key, 'heritage_press');
    }

    public static function find($id) {
        // Try cache first
        $cached = static::getFromCache($id);
        if ($cached) {
            return $cached;
        }

        global $wpdb;
        $class = get_called_class();
        $model = new $class();
        $table = $wpdb->prefix . 'heritage_press_' . $model->table;

        $data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        if ($data) {
            $model->fill($data);
            $model->cacheModel();
            return $model;
        }

        return null;
    }

    public function save() {
        global $wpdb;
        $table = $wpdb->prefix . 'heritage_press_' . $this->table;

        if (!$this->validate()) {
            return false;
        }

        if (isset($this->data['id'])) {
            $this->fireEvent('updating');
            $result = $wpdb->update(
                $table,
                $this->data,
                ['id' => $this->data['id']]
            );
            if ($result !== false) {
                $this->fireEvent('updated');
            }
            return $result;
        }

        $this->fireEvent('creating');
        $result = $wpdb->insert($table, $this->data);
        if ($result !== false) {
            $this->data['id'] = $wpdb->insert_id;
            $this->fireEvent('created');
        }
        return $result;
    }

    public function delete() {
        global $wpdb;
        $table = $wpdb->prefix . 'heritage_press_' . $this->table;

        if (!isset($this->data['id'])) {
            return false;
        }

        $this->fireEvent('deleting');
        $result = $wpdb->delete(
            $table,
            ['id' => $this->data['id']]
        );
        if ($result !== false) {
            $this->fireEvent('deleted');
        }
        return $result;
    }

    /**
     * Validate model data
     * 
     * @return bool True if validation passes, false otherwise
     */
    public function validate() {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $value = $this->data[$field] ?? null;
            foreach ($rules as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $this->errors[$field][] = "$field is required";
                } elseif ($rule === 'date' && !empty($value)) {
                    if (!strtotime($value)) {
                        $this->errors[$field][] = "$field must be a valid date";
                    }
                }                elseif (strpos($rule, 'max:') === 0) {
                    $max = substr($rule, 4);
                    if ($value !== null && strlen((string)$value) > $max) {
                        $this->errors[$field][] = "$field must not exceed $max characters";
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function isValid() {
        return $this->validate();
    }

    /**
     * Get validation errors
     * 
     * @return array Validation errors by field
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Create a new query builder instance
     */
    public static function query() {
        $class = get_called_class();
        return new Query_Builder(new $class());
    }

    /**
     * Get table name
     */
    public function getTable() {
        return $this->table;
    }

    /**
     * Find records by a field value
     */
    public static function findBy($field, $value) {
        $class = get_called_class();
        $model = new $class();
        $cacheKey = sprintf('%s_%s_%s', $field, $value, md5(serialize(static::$cacheTags)));
        
        // Try cache first
        $cached = $model->getCachedQueryResults($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = static::query()
            ->where($field, $value)
            ->first();

        if ($result) {
            $model->cacheQueryResults($cacheKey, $result);
        }

        return $result;
    }

    /**
     * Find multiple records by a field value
     */
    public static function findAllBy($field, $value) {
        $class = get_called_class();
        $model = new $class();
        $cacheKey = sprintf('all_%s_%s_%s', $field, $value, md5(serialize(static::$cacheTags)));
        
        // Try cache first
        $cached = $model->getCachedQueryResults($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $results = static::query()
            ->where($field, $value)
            ->get();

        if ($results) {
            $model->cacheQueryResults($cacheKey, $results);
        }

        return $results;
    }

    /**
     * Find active records
     */
    public static function findActive() {
        $class = get_called_class();
        $model = new $class();
        $cacheKey = sprintf('active_%s', md5(serialize(static::$cacheTags)));
        
        // Try cache first
        $cached = $model->getCachedQueryResults($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $results = static::query()
            ->where('status', 'active')
            ->get();

        if ($results) {
            $model->cacheQueryResults($cacheKey, $results);
        }

        return $results;
    }

    /**
     * Define a one-to-many relationship
     */
    public function hasMany(string $related, string $foreignKey, string $localKey = 'id') {
        $relationKey = sprintf('%s_%s_%s', $related, $foreignKey, $localKey);
        
        // Check if relation is already loaded
        if (isset($this->relationships[$relationKey])) {
            return $this->relationships[$relationKey];
        }

        $query = (new $related())->query();
        $results = $query->where($foreignKey, $this->$localKey);
        
        // Cache the relationship
        $this->relationships[$relationKey] = $results;
        $this->cacheModel();
        
        return $results;
    }

    /**
     * Define a belongs-to relationship
     */
    public function belongsTo(string $related, string $foreignKey, string $ownerKey = 'id') {
        $relationKey = sprintf('%s_%s_%s', $related, $foreignKey, $ownerKey);
        
        // Check if relation is already loaded
        if (isset($this->relationships[$relationKey])) {
            return $this->relationships[$relationKey];
        }

        $instance = new $related();
        if (!$this->$foreignKey) {
            return null;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'heritage_press_' . $instance->getTable();
        
        // Try getting from cache first
        $cacheKey = sprintf('%s_%s_%s', $related, $ownerKey, $this->$foreignKey);
        $cached = $instance->getCachedQueryResults($cacheKey);
        if ($cached !== null) {
            $this->relationships[$relationKey] = $cached;
            return $cached;
        }

        $sql = $wpdb->prepare("SELECT * FROM $table WHERE $ownerKey = %s", $this->$foreignKey);
        $result = $wpdb->get_row($sql);
        
        if ($result) {
            $instance->fill((array)$result);
            $this->relationships[$relationKey] = $instance;
            $instance->cacheModel();
            $instance->cacheQueryResults($cacheKey, $instance);
            return $instance;
        }
        
        return null;
    }

    /**
     * Define a many-to-many relationship
     */
    public function belongsToMany(string $related, string $table, string $foreignKey, string $relatedKey) {
        $relationKey = sprintf('%s_%s_%s_%s', $related, $table, $foreignKey, $relatedKey);
        
        // Check if relation is already loaded
        if (isset($this->relationships[$relationKey])) {
            return $this->relationships[$relationKey];
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'heritage_press_';
        
        $query = (new $related())->query();
        $results = $query->join(
            $table, 
            "{$prefix}{$table}.{$relatedKey}", 
            '=', 
            "{$query->getTable()}.id"
        )->where("{$prefix}{$table}.{$foreignKey}", $this->id);

        // Cache the results
        $this->relationships[$relationKey] = $results;
        $this->cacheModel();
        
        return $results;
    }

    /**
     * Load a relationship if not already loaded
     */
    public function load($relation) {
        if (!isset($this->relationships[$relation]) && method_exists($this, $relation)) {
            $this->relationships[$relation] = $this->$relation();
        }
        return $this->relationships[$relation] ?? null;
    }
}
