<?php
namespace HeritagePress\Database;

/**
 * Query Builder for genealogy models
 */
class QueryBuilder {
    private $model;
    private $table;
    private $where = [];
    private $order = [];
    private $limit;
    private $offset;
    private $joins = [];

    public function __construct($model) {
        global $wpdb;
        $this->model = $model;
        $this->table = $wpdb->prefix . 'heritage_press_' . $model->getTable();
    }

    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'type' => 'AND'
        ];
        
        return $this;
    }

    public function orWhere($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'type' => 'OR'
        ];
        
        return $this;
    }

    public function whereIn($column, array $values) {
        $this->where[] = [
            'column' => $column,
            'operator' => 'IN',
            'value' => $values,
            'type' => 'AND'
        ];
        
        return $this;
    }    public function join($table, $first, $operator, $second) {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $wpdb->prefix . 'heritage_press_' . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        
        return $this;
    }    public function leftJoin($table, $first, $operator, $second) {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $wpdb->prefix . 'heritage_press_' . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        $this->order[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        
        return $this;
    }

    public function limit($limit) {
        $this->limit = (int) $limit;
        return $this;
    }

    public function offset($offset) {
        $this->offset = (int) $offset;
        return $this;
    }

    public function get() {
        global $wpdb;
        
        $query = "SELECT {$this->table}.* FROM {$this->table}";
        
        // Add joins
        foreach ($this->joins as $join) {
            $query .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }
        
        // Add where clauses
        if (!empty($this->where)) {
            $query .= " WHERE";
            foreach ($this->where as $i => $where) {
                if ($i > 0) {
                    $query .= " {$where['type']}";
                }
                
                if ($where['operator'] === 'IN') {
                    $placeholders = implode(',', array_fill(0, count($where['value']), '%s'));
                    $query .= " {$where['column']} IN ($placeholders)";
                } else {
                    $query .= " {$where['column']} {$where['operator']} %s";
                }
            }
        }
        
        // Add order by
        if (!empty($this->order)) {
            $query .= " ORDER BY";
            foreach ($this->order as $i => $order) {
                if ($i > 0) {
                    $query .= ",";
                }
                $query .= " {$order['column']} {$order['direction']}";
            }
        }
        
        // Add limit and offset
        if ($this->limit !== null) {
            $query .= " LIMIT %d";
            if ($this->offset !== null) {
                $query .= " OFFSET %d";
            }
        }
        
        // Prepare values for query
        $values = [];
        foreach ($this->where as $where) {
            if ($where['operator'] === 'IN') {
                $values = array_merge($values, $where['value']);
            } else {
                $values[] = $where['value'];
            }
        }
        
        if ($this->limit !== null) {
            $values[] = $this->limit;
            if ($this->offset !== null) {
                $values[] = $this->offset;
            }
        }
        
        // Execute query
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Convert to model instances
        return array_map(function($data) {
            $model = new $this->model();
            $model->fill($data);
            return $model;
        }, $results);
    }

    public function first() {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }

    public function count() {
        global $wpdb;
        
        $query = "SELECT COUNT(*) FROM {$this->table}";
        
        if (!empty($this->where)) {
            $query .= " WHERE";
            $values = [];
            foreach ($this->where as $i => $where) {
                if ($i > 0) {
                    $query .= " {$where['type']}";
                }
                if ($where['operator'] === 'IN') {
                    $placeholders = implode(',', array_fill(0, count($where['value']), '%s'));
                    $query .= " {$where['column']} IN ($placeholders)";
                    $values = array_merge($values, $where['value']);
                } else {
                    $query .= " {$where['column']} {$where['operator']} %s";
                    $values[] = $where['value'];
                }
            }
            $query = $wpdb->prepare($query, $values);
        }
        
        return (int) $wpdb->get_var($query);
    }
}
