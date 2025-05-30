<?php
/**
 * Base Model Class
 *
 * This abstract class provides common functionality for all models in the genealogy plugin.
 * It implements basic data storage, retrieval, and database operations.
 *
 * @package HeritagePress
 * @subpackage Models
 */

namespace HeritagePress\Models;

use HeritagePress\Database\QueryBuilder as Query_Builder;

/**
 * Abstract Model class
 * 
 * @property-read int $id Record ID in the database
 * @property-read string $uuid Unique identifier
 * @property-read string $file_id Associated GEDCOM tree ID
 * @property-read string $status Record status (active/archived)
 */
abstract class Model {
    protected $data = [];
    protected $table;
    protected $fillable = [];
    protected $rules = [];
    protected $errors = [];

    private static $observers = [];

    private static $cache = [];
    protected static $cacheEnabled = true;
    protected static $cacheTTL = 3600; // 1 hour default

    protected $relationships = [];    public function __construct(array $data = []) {
        $this->fill($data);
    }

    public function getTable() {
        return $this->table;
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

    public function getFillable() {
        return $this->fillable;
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
     * Get cache key for a model instance
     */    protected function getCacheKey(): string {
        return sprintf(
            'heritage_press_%s_%s',
            $this->table,
            $this->id ?? 'new'
        );
    }

    /**
     * Cache model data
     */
    protected function cacheModel(): void {
        if (!static::$cacheEnabled || !isset($this->data['id'])) {
            return;
        }        wp_cache_set(
            $this->getCacheKey(),
            $this->data,
            'heritage_press',
            static::$cacheTTL
        );
    }

    /**
     * Get model from cache
     */
    protected static function getFromCache($id) {
        if (!static::$cacheEnabled) {
            return null;
        }        $class = get_called_class();
        $model = new $class();
        $data = wp_cache_get(
            sprintf('heritage_press_%s_%s', $model->table, $id),
            'heritage_press'
        );

        if ($data === false) {
            return null;
        }

        $model->fill($data);
        return $model;
    }

    /**
     * Save the model to the database
     * 
     * @return bool Whether the save was successful
     */
    public function save() {
        $this->fireEvent('saving');
        
        if (!$this->validate()) {
            return false;
        }

        $this->beforeSave();
        
        $query = new Query_Builder($this);
        
        if (!isset($this->data['id'])) {
            // Insert
            $this->data['created_at'] = current_time('mysql');
            $this->data['updated_at'] = current_time('mysql');
            $id = $query->insert($this->data);
            
            if ($id === false) {
                return false;
            }
            
            $this->data['id'] = $id;
        } else {
            // Update
            $this->data['updated_at'] = current_time('mysql');
            $query->where('id', $this->data['id']);
            if (!$query->update($this->data)) {
                return false;
            }
        }
        
        $this->fireEvent('saved');
        return true;
    }

    /**
     * Delete the model from the database
     * 
     * @return bool Whether the deletion was successful
     */    public function delete() {
        $this->fireEvent('deleting');
        
        if (!isset($this->data['id'])) {
            return false;
        }
        
        $query = new Query_Builder($this);
        $query->where('id', $this->data['id']);
        
        if (!$query->delete()) {
            return false;
        }
        
        $this->afterDelete();
        $this->fireEvent('deleted');
        return true;
    }

    /**
     * Validate the model data
     * 
     * @return bool Whether validation passed
     */
    protected function validate() {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $rules = explode('|', $rules);
            $value = isset($this->data[$field]) ? $this->data[$field] : null;
            
            foreach ($rules as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $this->errors[$field][] = "The {$field} field is required.";
                    continue;
                }
                
                if (strpos($rule, 'max:') === 0) {
                    $max = substr($rule, 4);
                    if (is_string($value) && strlen($value) > $max) {
                        $this->errors[$field][] = "The {$field} field must not exceed {$max} characters.";
                    }
                }
                
                if ($rule === 'numeric' && !is_numeric($value) && !empty($value)) {
                    $this->errors[$field][] = "The {$field} field must be numeric.";
                }
                
                if ($rule === 'date' && !empty($value)) {
                    $date = date_create($value);
                    if (!$date) {
                        $this->errors[$field][] = "The {$field} field must be a valid date.";
                    }
                }
                
                if (strpos($rule, 'in:') === 0) {
                    $allowed = explode(',', substr($rule, 3));
                    if (!empty($value) && !in_array($value, $allowed)) {
                        $this->errors[$field][] = "The {$field} field must be one of: " . implode(', ', $allowed);
                    }
                }
                
                if (strpos($rule, 'between:') === 0) {
                    list($min, $max) = explode(',', substr($rule, 8));
                    if (!empty($value) && ($value < $min || $value > $max)) {
                        $this->errors[$field][] = "The {$field} field must be between {$min} and {$max}.";
                    }
                }
            }
        }
        
        return empty($this->errors);
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Find a model by its primary key.
     *
     * @param int $id
     * @return static|null
     */
    public static function find($id) {
        $instance = new static();
        $query = new Query_Builder($instance);
        return $query->where('id', $id)->first();
    }

    /**
     * Find all models that match the given criteria.
     *
     * @param array $criteria
     * @return array
     */
    public static function findAll(array $criteria = []) {
        $instance = new static();
        $query = new Query_Builder($instance);
        
        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }
        
        return $query->get();
    }

    /**
     * Clear the model cache
     */
    protected function clearCache() {
        $class = get_class($this);
        foreach (static::$cache as $key => $value) {
            if (strpos($key, $class) === 0) {
                unset(static::$cache[$key]);
            }
        }
    }

    /**
     * Clear model cache when saving or deleting
     */
    protected function beforeSave() {
        $this->clearCache();
    }

    protected function afterDelete() {
        $this->clearCache();
    }
}
