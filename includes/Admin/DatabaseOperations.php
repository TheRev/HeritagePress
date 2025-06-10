<?php
namespace HeritagePress\Admin;

/**
 * Trait for database operations used in the Admin class
 */
trait DatabaseOperations
{
    private function get_trees($search_query = '', $privacy_filter = '')
    {
        $where_clauses = array('1=1');
        $query_args = array();

        if (!empty($search_query)) {
            $where_clauses[] = '(name LIKE %s OR description LIKE %s)';
            $query_args[] = '%' . $this->wpdb->esc_like($search_query) . '%';
            $query_args[] = '%' . $this->wpdb->esc_like($search_query) . '%';
        }

        if (!empty($privacy_filter)) {
            if ($privacy_filter === 'public') {
                $where_clauses[] = 'is_public = 1';
            } elseif ($privacy_filter === 'private') {
                $where_clauses[] = 'is_public = 0';
            }
        }

        $where_clause = implode(' AND ', $where_clauses);
        $order_clause = 'ORDER BY name ASC';

        $query = "SELECT * FROM {$this->wpdb->prefix}hp_trees WHERE {$where_clause} {$order_clause}";

        if (!empty($query_args)) {
            return $this->wpdb->get_results($this->wpdb->prepare($query, $query_args));
        }

        return $this->wpdb->get_results($query);
    }
    public function get_event_types()
    {
        $query = "SELECT * FROM {$this->wpdb->prefix}hp_event_types ORDER BY name ASC";
        return $this->wpdb->get_results($query);
    }

    public function get_recent_families($limit = 50)
    {
        $query = $this->wpdb->prepare(
            "SELECT f.*, i1.id as father_id, i2.id as mother_id 
             FROM {$this->wpdb->prefix}hp_families f 
             LEFT JOIN {$this->wpdb->prefix}hp_individuals i1 ON f.father_id = i1.id 
             LEFT JOIN {$this->wpdb->prefix}hp_individuals i2 ON f.mother_id = i2.id 
             ORDER BY f.updated_at DESC LIMIT %d",
            $limit
        );
        return $this->wpdb->get_results($query);
    }

    public function get_recent_sources($limit = 50)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_sources 
             ORDER BY updated_at DESC LIMIT %d",
            $limit
        );
        return $this->wpdb->get_results($query);
    }

    public function get_recent_media($limit = 50)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_media 
             ORDER BY updated_at DESC LIMIT %d",
            $limit
        );
        return $this->wpdb->get_results($query);
    }

    public function get_dna_tests($limit = 50)
    {
        $query = $this->wpdb->prepare(
            "SELECT d.*, i.id as individual_id, i.sex, 
                    GROUP_CONCAT(DISTINCT n.given_names) as given_names,
                    GROUP_CONCAT(DISTINCT n.surname) as surname
             FROM {$this->wpdb->prefix}hp_dna_tests d
             LEFT JOIN {$this->wpdb->prefix}hp_individuals i ON d.individual_id = i.id
             LEFT JOIN {$this->wpdb->prefix}hp_names n ON i.id = n.individual_id
             GROUP BY d.id
             ORDER BY d.test_date DESC LIMIT %d",
            $limit
        );
        return $this->wpdb->get_results($query);
    }

    private function get_individual($id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_individuals WHERE id = %d",
            $id
        );
        return $this->wpdb->get_row($query);
    }

    /**
     * Get name IDs for an individual
     * 
     * @param int $individual_id Individual ID to get names for
     * @return array Array of name objects
     */
    private function get_name_ids($individual_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}hp_names WHERE individual_id = %d",
            $individual_id
        );
        return $this->wpdb->get_col($query);
    }

    /**
     * Get name data for an individual
     * 
     * @param int $individual_id Individual ID to get names for
     * @return array Array of name objects
     */
    private function get_individual_names($individual_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_names WHERE individual_id = %d ORDER BY is_primary DESC, name_order ASC",
            $individual_id
        );
        return $this->wpdb->get_results($query);
    }

    /**
     * Update primary name for an individual
     * 
     * @param int $individual_id Individual ID
     * @param array $data Name data to update
     * @return bool True on success, false on failure
     */
    private function update_primary_name($individual_id, $data)
    {
        $data['updated_at'] = current_time('mysql');
        return $this->wpdb->update(
            $this->wpdb->prefix . 'hp_names',
            $data,
            [
                'individual_id' => $individual_id,
                'is_primary' => 1
            ]
        );
    }

    private function get_individual_events($individual_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_events WHERE individual_id = %d ORDER BY event_date ASC, event_type ASC",
            $individual_id
        );
        return $this->wpdb->get_results($query);
    }

    private function create_individual($data)
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }

        return $this->wpdb->insert(
            $this->wpdb->prefix . 'hp_individuals',
            $data
        );
    }

    private function create_name($data)
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }

        return $this->wpdb->insert(
            $this->wpdb->prefix . 'hp_names',
            $data
        );
    }

    private function create_event($data)
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }

        return $this->wpdb->insert(
            $this->wpdb->prefix . 'hp_events',
            $data
        );
    }

    private function update_individual($id, $data)
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }

        return $this->wpdb->update(
            $this->wpdb->prefix . 'hp_individuals',
            $data,
            ['id' => $id]
        );
    }

    private function delete_individual($id)
    {
        return $this->wpdb->delete(
            $this->wpdb->prefix . 'hp_individuals',
            ['id' => $id]
        );
    }

    private function merge_individuals($primary_id, $secondary_id)
    {
        // Start transaction
        $this->wpdb->query('START TRANSACTION');

        try {
            // Update names to point to primary individual
            $this->wpdb->update(
                $this->wpdb->prefix . 'hp_names',
                ['individual_id' => $primary_id],
                ['individual_id' => $secondary_id]
            );

            // Update events to point to primary individual
            $this->wpdb->update(
                $this->wpdb->prefix . 'hp_events',
                ['individual_id' => $primary_id],
                ['individual_id' => $secondary_id]
            );

            // Delete secondary individual
            $deleted = $this->delete_individual($secondary_id);

            if ($deleted) {
                $this->wpdb->query('COMMIT');
                return true;
            } else {
                $this->wpdb->query('ROLLBACK');
                return false;
            }
        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return false;
        }
    }

    private function create_tree($data)
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }

        return $this->wpdb->insert(
            $this->wpdb->prefix . 'hp_trees',
            $data
        );
    }

    private function update_tree($id, $data)
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }

        return $this->wpdb->update(
            $this->wpdb->prefix . 'hp_trees',
            $data,
            ['id' => $id]
        );
    }

    private function delete_tree($id)
    {
        return $this->wpdb->delete(
            $this->wpdb->prefix . 'hp_trees',
            ['id' => $id]
        );
    }

    private function search_individuals($search_term)
    {
        $query = $this->wpdb->prepare(
            "SELECT i.*, n.given_names, n.surname 
             FROM {$this->wpdb->prefix}hp_individuals i 
             LEFT JOIN {$this->wpdb->prefix}hp_names n ON i.id = n.individual_id AND n.is_primary = 1
             WHERE n.given_names LIKE %s OR n.surname LIKE %s OR i.gedcom_id LIKE %s
             ORDER BY n.surname, n.given_names
             LIMIT 50",
            '%' . $this->wpdb->esc_like($search_term) . '%',
            '%' . $this->wpdb->esc_like($search_term) . '%',
            '%' . $this->wpdb->esc_like($search_term) . '%'
        );

        return $this->wpdb->get_results($query);
    }

    /**
     * Get individual's tree membership
     * 
     * @param int $individual_id Individual ID
     * @return array|null Tree membership data or null if not found
     */
    private function get_individual_tree($individual_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT t.* FROM {$this->wpdb->prefix}hp_trees t 
             JOIN {$this->wpdb->prefix}hp_individuals i ON t.id = i.tree_id
             WHERE i.id = %d",
            $individual_id
        );
        return $this->wpdb->get_row($query);
    }

    /**
     * Get family record
     * 
     * @param int $family_id Family ID
     * @return array|null Family data or null if not found
     */
    private function get_family($family_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_families WHERE id = %d",
            $family_id
        );
        return $this->wpdb->get_row($query);
    }

    /**
     * Get tree record
     * 
     * @param int $tree_id Tree ID
     * @return array|null Tree data or null if not found
     */
    private function get_tree($tree_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_trees WHERE id = %d",
            $tree_id
        );
        return $this->wpdb->get_row($query);
    }
}
