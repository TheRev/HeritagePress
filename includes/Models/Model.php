<?php
namespace HeritagePress\Models;

/**
 * Base model class for HeritagePress
 */
abstract class Model
{
    /**
     * Database table name
     *
     * @var string
     */
    protected $table;

    /**
     * Primary key column name
     *
     * @var string
     */
    protected $primary_key = 'id';

    /**
     * Database connection
     *
     * @var \wpdb
     */
    protected $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'hp_' . $this->table;
    }

    /**
     * Find a record by ID
     *
     * @param int $id Record ID
     * @return object|null Database row
     */
    public function find($id)
    {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE {$this->primary_key} = %d",
                $id
            )
        );
    }

    /**
     * Insert a new record
     *
     * @param array $data Record data
     * @return int|false The number of rows inserted, or false on error
     */
    public function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update a record
     *
     * @param int   $id   Record ID
     * @param array $data Record data
     * @return int|false The number of rows updated, or false on error
     */
    public function update($id, $data)
    {
        return $this->db->update(
            $this->table,
            $data,
            [$this->primary_key => $id]
        );
    }

    /**
     * Delete a record
     *
     * @param int $id Record ID
     * @return int|false The number of rows deleted, or false on error
     */
    public function delete($id)
    {
        return $this->db->delete(
            $this->table,
            [$this->primary_key => $id]
        );
    }
}
