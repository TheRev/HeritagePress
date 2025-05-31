<?php
namespace HeritagePress\Tests\Mocks;

class MockWPDB {
    protected $tables = [];
    public $data = [];
    protected $lastInsertId = 0;
    protected $lastQuery;
    public $prefix = 'wp_';
    protected $queries = [];
    protected $row_callback = null;
    public $insert_id;
    public $last_error = '';

    public function __construct() {
        $this->tables = [];
        $this->data = [];
        $this->queries = [];
    }

    public function addTable($name, $columns = []) {
        error_log("MockWPDB addTable: " . $name);
        $this->tables[$name] = $columns;
        if (!isset($this->data[$name])) {
            $this->data[$name] = [];
        }
        error_log("MockWPDB tables: " . print_r($this->tables, true));
        error_log("MockWPDB data: " . print_r($this->data, true));
    }

    public function query($query) {
        $this->lastQuery = $query;
        $this->queries[] = $query;
        
        if (stripos($query, 'DELETE FROM') === 0) {
            $table = trim(substr($query, 12));
            if (isset($this->data[$table])) {
                $this->data[$table] = [];
            }
            return true;
        }
        
        return false;
    }

    private function parseWhereConditions($query) {
        if (preg_match('/WHERE\s+(.+?)(?:\s+ORDER\s+BY|\s+LIMIT|\s*$)/i', $query, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }    public function insert($table, $data, $format = null) {
        error_log("MockWPDB insert into table: " . $table);
        error_log("MockWPDB data: " . print_r($data, true));
        error_log("MockWPDB all data before insert: " . print_r($this->data, true));

        if (!isset($this->data[$table])) {
            $this->data[$table] = [];
        }

        $id = ++$this->lastInsertId;
        $data['id'] = $id;
        $this->data[$table][] = $data;
        $this->insert_id = $id;
        
        error_log("MockWPDB all data after insert: " . print_r($this->data, true));
        return true;
    }

    public function update($table, $data, $where, $format = null, $where_format = null) {
        if (!isset($this->data[$table])) {
            return false;
        }

        $updated = false;
        foreach ($this->data[$table] as $key => $row) {
            $match = true;
            foreach ($where as $field => $value) {
                if (!isset($row[$field]) || $row[$field] != $value) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                $this->data[$table][$key] = array_merge($row, $data);
                $updated = true;
            }
        }
        
        return $updated;
    }

    public function get_results($query, $output = OBJECT) {
        $this->lastQuery = $query;
        $this->queries[] = $query;
        error_log("MockWPDB get_results query: " . $query);
          // Basic parsing of SELECT queries
        if (preg_match('/SELECT .* FROM ([^\s]+)( WHERE (.+))?/i', $query, $matches)) {
            $table = trim($matches[1]);
            error_log("MockWPDB table: " . $table);
            error_log("MockWPDB data: " . print_r($this->data, true));
            
            if (!isset($this->data[$table])) {
                error_log("MockWPDB table not found: " . $table);
                return [];
            }
            
            $results = $this->data[$table];
              // Handle WHERE conditions
            if (!empty($matches[3])) {
                $where = $matches[3];
                error_log("MockWPDB WHERE: " . $where);
                $results = array_filter($results, function($row) use ($where) {
                    // Handle AND conditions
                    $conditions = preg_split('/\s+AND\s+/i', $where);
                    foreach ($conditions as $condition) {
                        $condition = trim($condition);
                        
                        // Handle IS NULL
                        if (preg_match('/([^\s]+)\s+IS\s+NULL/i', $condition, $cond)) {
                            $field = $cond[1];
                            if (isset($row[$field]) && $row[$field] !== null) {
                                return false;
                            }
                        }
                        // Handle IS NOT NULL
                        elseif (preg_match('/([^\s]+)\s+IS\s+NOT\s+NULL/i', $condition, $cond)) {
                            $field = $cond[1];
                            if (!isset($row[$field]) || $row[$field] === null) {
                                return false;
                            }
                        }
                        // Handle = conditions
                        elseif (preg_match('/([^\s]+)\s*=\s*\'?([^\']+)\'?/', $condition, $cond)) {
                            $field = $cond[1];
                            $value = trim($cond[2], "'");
                            if (!isset($row[$field]) || $row[$field] != $value) {
                                return false;
                            }
                        }
                    }
                    return true;
                });
            }
            
            error_log("MockWPDB results before output conversion: " . print_r($results, true));
            
            if ($output === ARRAY_A) {
                return array_values($results);
            }
            
            // Convert to objects if needed
            $results = array_map(function($row) {
                return (object) $row;
            }, array_values($results));
            
            error_log("MockWPDB final results: " . print_r($results, true));
            return $results;
        }
        
        error_log("MockWPDB query did not match SELECT pattern");
        return [];
    }    public function prepare($query, ...$args) {
        foreach ($args as $arg) {
            // Handle %d (integer)
            $pos = strpos($query, '%d');
            if ($pos !== false) {
                $query = substr_replace($query, intval($arg), $pos, 2);
                continue;
            }
            
            // Handle %s (string)
            $pos = strpos($query, '%s');
            if ($pos !== false) {
                $query = substr_replace($query, "'" . $this->escape($arg) . "'", $pos, 2);
                continue;
            }
        }
        return $query;
    }public function escape($data) {
        if (is_array($data)) {
            $escaped = [];
            foreach ($data as $item) {
                $escaped[] = $this->escape($item);
            }
            return implode(',', $escaped);
        }
        return is_string($data) ? addslashes($data) : $data;
    }

    public function get_row($query, $output = OBJECT, $y = 0) {
        $results = $this->get_results($query, $output);
        return !empty($results) ? $results[0] : null;
    }    public function get_var($query) {
        $this->lastQuery = $query;
        $this->queries[] = $query;
        
        // Handle SHOW TABLES LIKE queries
        if (preg_match('/SHOW\s+TABLES\s+LIKE\s+[\'"]([^\'"]+)[\'"]/i', $query, $matches)) {
            $table_name = $matches[1];
            return isset($this->tables[$table_name]) ? $table_name : null;
        }
        
        // Handle SELECT queries that return a single value
        if (stripos($query, 'SELECT') === 0) {
            $results = $this->get_results($query, ARRAY_A);
            if (!empty($results) && is_array($results[0])) {
                return array_values($results[0])[0] ?? null;
            }
        }
        
        return null;
    }

    public function get_col($query, $x = 0) {
        $this->lastQuery = $query;
        $this->queries[] = $query;
        return [];
    }

    public function insert_id() {
        return $this->lastInsertId;
    }

    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
    }

    public function get_queries() {
        return $this->queries;
    }

    public function clear_queries() {
        $this->queries = [];
    }

    public function setRowCallback(callable $callback) {
        $this->row_callback = $callback;
    }

    public function delete($table, $where, $where_format = null) {
        $this->lastQuery = "DELETE FROM $table WHERE " . implode(' AND ', array_map(function($k, $v) {
            return "$k = '$v'";
        }, array_keys($where), $where));
        $this->queries[] = $this->lastQuery;
        
        if (!isset($this->data[$table])) {
            return false;
        }
        
        $deleted_count = 0;
        foreach ($this->data[$table] as $key => $row) {
            $match = true;
            foreach ($where as $field => $value) {
                if (!isset($row[$field]) || $row[$field] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                unset($this->data[$table][$key]);
                $deleted_count++;
            }
        }
        
        return $deleted_count > 0 ? $deleted_count : false;
    }
}
