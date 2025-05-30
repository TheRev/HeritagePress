<?php
namespace HeritagePress\Models\Relations;

abstract class BaseRelation {
    /**
     * The related model instance
     */
    protected $related;

    /**
     * The parent model instance
     */
    protected $parent;

    /**
     * The foreign key of the relationship
     */
    protected $foreignKey;

    /**
     * The local key of the relationship
     */
    protected $localKey;

    /**
     * Constructor
     */
    public function __construct($related, $parent, $foreignKey, $localKey) {
        $this->related = $related;
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    /**
     * Get the related model instance
     */
    public function getRelated() {
        return $this->related;
    }

    /**
     * Get the parent model instance
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Get the foreign key
     */
    public function getForeignKey() {
        return $this->foreignKey;
    }

    /**
     * Get the local key
     */
    public function getLocalKey() {
        return $this->localKey;
    }

    /**
     * Execute the relationship query
     */
    abstract public function get();
}
