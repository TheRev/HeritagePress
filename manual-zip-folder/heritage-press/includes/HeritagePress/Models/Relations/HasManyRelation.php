<?php
namespace HeritagePress\Models\Relations;

class HasManyRelation extends BaseRelation {
    /**
     * Execute the relationship query
     */
    public function get() {
        return $this->related->where($this->foreignKey, $this->parent->{$this->localKey})->get();
    }
}
