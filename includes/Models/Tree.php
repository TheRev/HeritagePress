<?php
namespace HeritagePress\Models;

/**
 * Tree Model Class for HeritagePress
 * Handles database operations for genealogy trees (GEDCOM files)
 * Based on hp_trees table structure
 */
class Tree extends Model
{
    /**
     * Database table name (without prefix)
     *
     * @var string
     */
    protected $table = 'trees';

    /**
     * Primary key column name
     *
     * @var string
     */
    protected $primary_key = 'treeID';    /**
            * Fillable columns for mass assignment
            *
            * @var array
            */
    protected $fillable = [
        'gedcom',
        'title',
        'description',
        'owner',
        'email',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'phone',
        'privacy_level',
        'private',
        'disallowgedcreate',
        'disallowpdf',
        'owner_user_id',
        'rootpersonID'
    ];    /**
         * Get all trees with optional ordering
         *
         * @param string $order_by Column to order by
         * @param string $order Direction (ASC|DESC)
         * @return array
         */
    public function getAllTrees($order_by = 'title', $order = 'ASC')
    {
        // Sanitize order by - only allow specific columns
        $allowed_columns = ['title', 'gedcom', 'created_at', 'updated_at', 'owner'];
        $order_by = in_array($order_by, $allowed_columns) ? $order_by : 'title';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        return $this->db->get_results(
            "SELECT * FROM {$this->table} ORDER BY {$order_by} {$order}"
        );
    }

    /**
     * Find tree by GEDCOM name
     *
     * @param string $gedcom GEDCOM identifier
     * @return object|null
     */
    public function findByGedcom($gedcom)
    {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE gedcom = %s",
                $gedcom
            )
        );
    }

    /**
     * Create a new tree record
     *
     * @param array $data Tree data
     * @return int|false Tree ID on success, false on failure
     */
    public function create($data)
    {
        // Filter only fillable fields
        $filtered_data = array_intersect_key($data, array_flip($this->fillable));

        // Add timestamps
        $filtered_data['created_at'] = current_time('mysql');
        $filtered_data['updated_at'] = current_time('mysql');

        $result = $this->db->insert($this->table, $filtered_data);

        return $result ? $this->db->insert_id : false;
    }

    /**
     * Update a tree record
     *
     * @param int $tree_id Tree ID
     * @param array $data Tree data
     * @return bool Success status
     */
    public function update($tree_id, $data)
    {
        // Filter only fillable fields
        $filtered_data = array_intersect_key($data, array_flip($this->fillable));

        // Add update timestamp
        $filtered_data['updated_at'] = current_time('mysql');

        $result = $this->db->update(
            $this->table,
            $filtered_data,
            [$this->primary_key => $tree_id],
            null,
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Delete a tree and all associated data
     *
     * @param int $tree_id Tree ID
     * @return bool Success status
     */
    public function delete($tree_id)
    {
        // Get tree record first
        $tree = $this->find($tree_id);
        if (!$tree) {
            return false;
        }

        // Start transaction
        $this->db->query('START TRANSACTION');

        try {
            // Delete from related tables first (based on gedcom)
            $tables_to_clean = [
                'hp_people',
                'hp_families',
                'hp_events',
                'hp_sources',
                'hp_media',
                'hp_xnotes',
                'hp_citations'
            ];

            foreach ($tables_to_clean as $table) {
                $full_table = $this->db->prefix . $table;
                $this->db->delete($full_table, ['gedcom' => $tree->gedcom]);
            }

            // Finally delete the tree record
            $result = $this->db->delete(
                $this->table,
                [$this->primary_key => $tree_id],
                ['%d']
            );

            if ($result) {
                $this->db->query('COMMIT');
                return true;
            } else {
                $this->db->query('ROLLBACK');
                return false;
            }
        } catch (\Exception $e) {
            $this->db->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Get tree statistics
     *
     * @param int $tree_id Tree ID
     * @return array Statistics array
     */
    public function getTreeStats($tree_id)
    {
        $tree = $this->find($tree_id);
        if (!$tree) {
            return [];
        }

        $stats = [];
        $gedcom = $tree->gedcom;

        // Count individuals
        $stats['individuals'] = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix}hp_people WHERE gedcom = %s",
                $gedcom
            )
        );

        // Count families
        $stats['families'] = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix}hp_families WHERE gedcom = %s",
                $gedcom
            )
        );

        // Count sources
        $stats['sources'] = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix}hp_sources WHERE gedcom = %s",
                $gedcom
            )
        );

        // Count media
        $stats['media'] = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix}hp_media WHERE gedcom = %s",
                $gedcom
            )
        );

        return $stats;
    }

    /**
     * Check if GEDCOM name is unique
     *
     * @param string $gedcom GEDCOM identifier
     * @param int $exclude_id Tree ID to exclude from check
     * @return bool True if unique, false if exists
     */
    public function isGedcomUnique($gedcom, $exclude_id = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE gedcom = %s";
        $params = [$gedcom];

        if ($exclude_id) {
            $sql .= " AND {$this->primary_key} != %d";
            $params[] = $exclude_id;
        }

        $count = $this->db->get_var($this->db->prepare($sql, $params));
        return $count == 0;
    }

    /**
     * Get trees for a specific user
     *
     * @param int $user_id WordPress user ID
     * @return array
     */
    public function getTreesByUser($user_id)
    {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE owner_user_id = %d ORDER BY title ASC",
                $user_id
            )
        );
    }

    /**
     * Update tree statistics cache
     *
     * @param int $tree_id Tree ID
     * @return bool Success status
     */
    public function updateStatsCache($tree_id)
    {
        $stats = $this->getTreeStats($tree_id);

        // Store stats in wp_options for caching
        $option_name = "heritagepress_tree_stats_{$tree_id}";
        return update_option($option_name, $stats);
    }

    /**
     * Get cached tree statistics
     *
     * @param int $tree_id Tree ID
     * @return array|false Statistics or false if not cached
     */
    public function getCachedStats($tree_id)
    {
        $option_name = "heritagepress_tree_stats_{$tree_id}";
        return get_option($option_name, false);
    }
}
