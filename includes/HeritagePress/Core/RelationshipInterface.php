<?php
namespace HeritagePress\Core;

/**
 * Model Relationships Interface
 * 
 * Defines types of relationships between models
 */
interface RelationshipInterface {
    /**
     * One-to-many relationship
     */
    public function hasMany(string $related, string $foreignKey, string $localKey = 'id');

    /**
     * Belongs-to relationship
     */
    public function belongsTo(string $related, string $foreignKey, string $ownerKey = 'id');

    /**
     * Many-to-many relationship
     */
    public function belongsToMany(string $related, string $table, string $foreignKey, string $relatedKey);
}
