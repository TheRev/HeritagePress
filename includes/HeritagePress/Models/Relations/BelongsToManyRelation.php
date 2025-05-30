<?php
namespace HeritagePress\Models\Relations;

class BelongsToManyRelation extends BaseRelation {
    /**
     * The pivot table for the relationship
     */
    protected $table;

    /**
     * The foreign key of the relationship in the pivot table
     */
    protected $foreignPivotKey;

    /**
     * The related key of the relationship in the pivot table
     */
    protected $relatedPivotKey;

    /**
     * Constructor
     */
    public function __construct($related, $parent, $table, $foreignPivotKey, $relatedPivotKey) {
        $this->related = $related;
        $this->parent = $parent;
        $this->table = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
    }

    /**
     * Execute the relationship query
     */
    public function get() {
        return $this->related
            ->join($this->table, $this->related->getTable() . '.id', '=', $this->table . '.' . $this->relatedPivotKey)
            ->where($this->table . '.' . $this->foreignPivotKey, $this->parent->id)
            ->get();
    }
}
