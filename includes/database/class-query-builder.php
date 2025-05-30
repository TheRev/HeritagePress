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
        error_log("QueryBuilder constructed for table: " . $this->table);
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

    public function first() {
        global $wpdb;

        $sql = $this->compileSelect() . " LIMIT 1";
        error_log("QueryBuilder executing SQL: " . $sql);
        $result = $wpdb->get_row($sql, ARRAY_A);
        error_log("QueryBuilder result: " . print_r($result, true));

        if (!$result) {
            return null;
        }

        $model = get_class($this->model);
        $instance = new $model();
        $instance->fill($result);

        return $instance;
    }

    public function get() {
        global $wpdb;

        $sql = $this->compileSelect();
        error_log("QueryBuilder executing SQL: " . $sql);
        $results = $wpdb->get_results($sql, ARRAY_A);
        error_log("QueryBuilder results: " . print_r($results, true));

        if (!$results) {
            return [];
        }

        $models = [];
        $model = get_class($this->model);
        foreach ($results as $result) {
            $instance = new $model();
            $instance->fill($result);
            $models[] = $instance;
        }

        return $models;
    }

    protected function compileSelect() {
        $sql = "SELECT * FROM {$this->table}";

        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }

        if (!empty($this->where)) {
            $sql .= " WHERE ";
            foreach ($this->where as $i => $condition) {
                if ($i > 0) {
                    $sql .= " {$condition['type']} ";
                }

                if ($condition['operator'] === 'IN' && is_array($condition['value'])) {
                    $values = implode(',', array_map(function($val) {
                        return is_string($val) ? "'" . esc_sql($val) . "'" : $val;
                    }, $condition['value']));
                    $sql .= "{$condition['column']} IN ({$values})";
                } else {
                    $value = is_string($condition['value']) ? "'" . esc_sql($condition['value']) . "'" : $condition['value'];
                    $sql .= "{$condition['column']} {$condition['operator']} {$value}";
                }
            }
        }

        if (!empty($this->order)) {
            $sql .= " ORDER BY ";
            $orders = [];
            foreach ($this->order as $order) {
                $orders[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= implode(', ', $orders);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        error_log("QueryBuilder compiled SQL: " . $sql);
        return $sql;
    }

    protected function compileWhere() {
        $where = [];
        foreach ($this->where as $condition) {
            if ($condition['operator'] === '=') {
                $where[$condition['column']] = $condition['value'];
            }
        }
        return $where;
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

    public function insert(array $data) {
        global $wpdb;

        // Filter out any fields that aren't in the fillable array
        $fillable = $this->model->getFillable();
        $data = array_intersect_key($data, array_flip($fillable));

        $result = $wpdb->insert(
            $this->table,
            $data
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    public function update(array $data) {
        global $wpdb;

        // Filter out any fields that aren't in the fillable array
        $fillable = $this->model->getFillable();
        $data = array_intersect_key($data, array_flip($fillable));

        $where = $this->compileWhere();
        $whereData = array_column($this->where, 'value');

        $result = $wpdb->update(
            $this->table,
            $data,
            $where,
            null,
            null
        );

        return $result !== false;
    }

    public function delete() {
        global $wpdb;

        $where = $this->compileWhere();
        $whereData = array_column($this->where, 'value');

        $result = $wpdb->delete(
            $this->table,
            $where
        );

        return $result !== false;
    }
}
