<?php
namespace HeritagePress\Models\Traits;

trait HasRelationships {
    /**
     * Get a hasOne relationship
     */
    protected function hasOne($relatedClass, $foreignKey = null, $localKey = 'id') {
        $model = new $relatedClass();
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        
        return new HasOneRelation($model, $this, $foreignKey, $localKey);
    }
    
    /**
     * Get a hasMany relationship
     */
    protected function hasMany($relatedClass, $foreignKey = null, $localKey = 'id') {
        $model = new $relatedClass();
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        
        return new HasManyRelation($model, $this, $foreignKey, $localKey);
    }
    
    /**
     * Get a belongsTo relationship
     */
    protected function belongsTo($relatedClass, $foreignKey = null, $ownerKey = 'id') {
        $model = new $relatedClass();
        $foreignKey = $foreignKey ?: $this->guessBelongsToRelation();
        
        return new BelongsToRelation($model, $this, $foreignKey, $ownerKey);
    }
    
    /**
     * Get a belongsToMany relationship
     */
    protected function belongsToMany($relatedClass, $table = null, $foreignPivotKey = null, $relatedPivotKey = null) {
        $model = new $relatedClass();
        
        $table = $table ?: $this->joiningTable($model);
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?: $model->getForeignKey();
        
        return new BelongsToManyRelation($model, $this, $table, $foreignPivotKey, $relatedPivotKey);
    }
    
    /**
     * Get the default foreign key name for the model
     */
    protected function getForeignKey() {
        return strtolower(class_basename($this)) . '_id';
    }
    
    /**
     * Guess the foreign key for a belongs to relationship
     */
    protected function guessBelongsToRelation() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[2]['function'];
        
        return Str::snake($caller) . '_id';
    }
    
    /**
     * Get the joining table name for a many-to-many relationship
     */
    protected function joiningTable($related) {
        $models = [
            Str::snake(class_basename($this)),
            Str::snake(class_basename($related))
        ];
        sort($models);
        
        return strtolower(implode('_', $models));
    }
}
