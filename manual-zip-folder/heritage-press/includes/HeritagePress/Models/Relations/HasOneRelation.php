<?php
namespace HeritagePress\Models\Relations;

class HasOneRelation extends BaseRelation {
    /**
     * Execute the relationship query
     */
    public function get() {
        return $this->related->where($this->foreignKey, $this->parent->{$this->localKey})->first();
    }
}
