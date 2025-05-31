<?php
namespace HeritagePress\Models\Relations;

class BelongsToRelation extends BaseRelation {
    /**
     * Execute the relationship query
     */
    public function get() {
        return $this->related->where($this->localKey, $this->parent->{$this->foreignKey})->first();
    }
}
