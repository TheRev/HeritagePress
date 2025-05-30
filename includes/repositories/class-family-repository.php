<?php
/**
 * Family Repository Class
 *
 * @package HeritagePress
 */

namespace HeritagePress\Repositories;

use HeritagePress\Database\DatabaseManager;
use HeritagePress\Models\Family_Model; // Added
use HeritagePress\Core\Audit_Log_Observer; // Added

class Family_Repository {

    private $wpdb;
    private $table_name;
    private $audit_observer; // Added

    /**
     * Constructor
     */
    public function __construct(Audit_Log_Observer $audit_observer) { // Modified: Inject Audit_Log_Observer
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = DatabaseManager::get_table_prefix() . 'families';
        $this->audit_observer = $audit_observer; // Added
    }

    /**
     * Converts a database row (stdClass) to a Family_Model.
     *
     * @param \stdClass|null $db_row The database row.
     * @return Family_Model|null The family model or null if input is null.
     */    private function to_model(?\stdClass $db_row): ?Family_Model {
        if (!$db_row) {
            return null;
        }
        return Family_Model::from_db_object($db_row);
    }

    /**
     * Create a new family record.
     *
     * @param array $data Associative array of data for the new family.
     * @return Family_Model|false The created Family_Model on success, false on failure.
     */
    public function create(array $data) {
        if (empty($data['uuid']) || empty($data['file_id'])) {
            return false;
        }

        $defaults = [
            'husband_id' => null,
            'wife_id' => null,
            'marriage_date' => null,
            'marriage_place_id' => null,
            'divorce_date' => null,
            'divorce_place_id' => null,
            'user_reference_text' => null,
            'restriction_type' => null,
            'notes' => null,
            'shared_note_id' => null,
            'privacy' => 0,
            'status' => 'active',
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1),
            'deleted_at' => null,
        ];

        $data = wp_parse_args($data, $defaults);

        // Validate ENUM/SET fields
        if (isset($data['restriction_type'])) {
            $valid_restrictions = ['CONFIDENTIAL', 'LOCKED', 'PRIVACY'];
            $input_restrictions = is_array($data['restriction_type']) ? $data['restriction_type'] : explode(',', $data['restriction_type']);
            $validated_restrictions = array_intersect($input_restrictions, $valid_restrictions);
            $data['restriction_type'] = !empty($validated_restrictions) ? implode(',', $validated_restrictions) : null;
        } else {
            $data['restriction_type'] = null;
        }

        if (isset($data['status']) && !in_array($data['status'], ['active', 'archived', null], true)) {
            $data['status'] = 'active'; // Default to active if invalid
        }


        $this->audit_observer->creating(new Family_Model($data)); // Audit before creation attempt

        $result = $this->wpdb->insert($this->table_name, $data);

        if ($result === false) {
            return false;
        }
        $id = $this->wpdb->insert_id;
        $created_model = $this->get_by_id($id); // Fetch the full model
        if ($created_model) {
            $this->audit_observer->created($created_model);
        }
        return $created_model;
    }

    /**
     * Retrieve a family by its primary ID.
     *
     * @param int $id The ID of the family.
     * @param bool $with_trashed Whether to include soft-deleted records.
     * @return Family_Model|null The family model on success, null if not found or on error.
     */    public function get_by_id(int $id, bool $with_trashed = false): ?Family_Model {
        if ($id <= 0) {
            return null;
        }
        $query = $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id);
        if (!$with_trashed) {
            $query .= " AND deleted_at IS NULL";
        }
        $db_row = $this->wpdb->get_row($query);
        return $this->to_model($db_row);
    }

    /**
     * Retrieve a family by its UUID.
     *
     * @param string $uuid The UUID of the family.
     * @param bool $with_trashed Whether to include soft-deleted records.
     * @return Family_Model|null The family model on success, null if not found or on error.
     */
    public function get_by_uuid(string $uuid, bool $with_trashed = false): ?Family_Model {
        if (empty($uuid)) {
            return null;
        }
        $query = $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE uuid = %s", $uuid);
        if (!$with_trashed) {
            $query .= " AND deleted_at IS NULL";
        }
        $db_row = $this->wpdb->get_row($query);
        return $this->to_model($db_row);
    }
    
    /**
     * Retrieve all families.
     *
     * @param bool $with_trashed Whether to include soft-deleted records.
     * @return Family_Model[] Array of family models.
     */
    public function get_all(bool $with_trashed = false): array {
        $query = "SELECT * FROM {$this->table_name}";
        if (!$with_trashed) {
            $query .= " WHERE deleted_at IS NULL";
        }
        $results = $this->wpdb->get_results($query);
        $families = [];
        foreach ($results as $row) {
            $families[] = $this->to_model($row);
        }
        return array_filter($families); // Remove any nulls if to_model failed
    }


    /**
     * Update an existing family record by its primary ID.
     *
     * @param int $id The ID of the family to update.
     * @param array $data Associative array of data to update.
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool {
        if ($id <= 0 || empty($data)) {
            return false;
        }

        $old_model = $this->get_by_id($id, true); // Get even if trashed, for audit
        if (!$old_model) {
            return false; // Record doesn't exist
        }

        // Prevent changing immutable fields
        unset($data['uuid']);
        unset($data['file_id']);
        unset($data['id']);
        unset($data['created_at']); 
        unset($data['deleted_at']); // Soft delete status should be managed by delete/restore methods

        // Validate ENUM/SET fields
        if (isset($data['restriction_type'])) {
            $valid_restrictions = ['CONFIDENTIAL', 'LOCKED', 'PRIVACY'];
            $input_restrictions = is_array($data['restriction_type']) ? $data['restriction_type'] : explode(',', $data['restriction_type']);
            $validated_restrictions = array_intersect($input_restrictions, $valid_restrictions);
            $data['restriction_type'] = !empty($validated_restrictions) ? implode(',', $validated_restrictions) : null;
        } elseif (array_key_exists('restriction_type', $data) && $data['restriction_type'] === null) {
            $data['restriction_type'] = null;
        } else if (isset($data['restriction_type'])) {
             unset($data['restriction_type']);
        }

        if (isset($data['status']) && !in_array($data['status'], ['active', 'archived', null], true)) {
            unset($data['status']); // Remove if invalid
        }
        
        $data['updated_at'] = current_time('mysql', 1);

        // Create a temporary new model state for the observer
        $temp_new_data = array_merge($old_model->toArray(), $data);
        $new_model_for_observer = new Family_Model($temp_new_data);
        
        $this->audit_observer->updating($old_model); // Pass old model
        
        $result = $this->wpdb->update($this->table_name, $data, ['id' => $id]);

        if ($result === false) {
            return false;
        }

        $updated_model = $this->get_by_id($id, true); // Re-fetch the updated model
        if ($updated_model) {
            $this->audit_observer->updated($old_model, $updated_model);
        }
        return true;
    }

    /**
     * Soft delete a family record by its primary ID.
     *
     * @param int $id The ID of the family to soft delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        $model_to_delete = $this->get_by_id($id, true); // Get even if already soft-deleted, for audit
        if (!$model_to_delete || $model_to_delete->deleted_at !== null) {
            // Already deleted or doesn't exist
            return $model_to_delete && $model_to_delete->deleted_at !== null; // True if already deleted
        }

        $this->audit_observer->deleting($model_to_delete);

        $result = $this->wpdb->update(
            $this->table_name,
            ['deleted_at' => current_time('mysql', 1)],
            ['id' => $id]
        );

        if ($result === false) {
            return false;
        }
        $deleted_model = $this->get_by_id($id, true); // Re-fetch to pass to observer
        if ($deleted_model) {
             $this->audit_observer->deleted($deleted_model); // Or use $model_to_delete if state before update is preferred
        }
        return true;
    }

    /**
     * Restore a soft-deleted family record by its primary ID.
     *
     * @param int $id The ID of the family to restore.
     * @return bool True on success, false on failure.
     */
    public function restore(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        $model_to_restore = $this->get_by_id($id, true);
        if (!$model_to_restore || $model_to_restore->deleted_at === null) {
            // Not deleted or doesn't exist
            return $model_to_restore && $model_to_restore->deleted_at === null; // True if not deleted
        }

        $result = $this->wpdb->update(
            $this->table_name,
            ['deleted_at' => null, 'updated_at' => current_time('mysql', 1)],
            ['id' => $id]
        );

        if ($result === false) {
            return false;
        }
        $restored_model = $this->get_by_id($id);
        if ($restored_model) {
            $this->audit_observer->restored($restored_model);
        }
        return true;
    }

    /**
     * Permanently delete a family record by its primary ID.
     *
     * @param int $id The ID of the family to delete.
     * @return bool True on success, false on failure.
     */
    public function force_delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        $model_to_force_delete = $this->get_by_id($id, true); // Get for audit log, even if soft-deleted
        if (!$model_to_force_delete) {
            return false; // Doesn't exist
        }

        $this->audit_observer->force_deleting($model_to_force_delete);

        $result = $this->wpdb->delete($this->table_name, ['id' => $id]);

        if ($result === false) {
            return false;
        }
        // Note: After force_delete, the model is gone. $model_to_force_delete holds its last state.
        // The `deleted` event is typically for soft deletes. For force delete, `force_deleting` is the primary audit.
        return true;
    }

    /**
     * Search families with advanced filtering
     *
     * @param array $criteria Search criteria
     * @return array Array of Family_Model objects
     */
    public function search(array $criteria = []): array {
        $where_conditions = ['deleted_at IS NULL']; // Exclude soft-deleted
        $where_values = [];

        // Build WHERE clause based on criteria
        if (!empty($criteria['husband_name'])) {
            $where_conditions[] = "husband_name LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($criteria['husband_name']) . '%';
        }

        if (!empty($criteria['wife_name'])) {
            $where_conditions[] = "wife_name LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($criteria['wife_name']) . '%';
        }

        if (!empty($criteria['marriage_date'])) {
            $where_conditions[] = "marriage_date = %s";
            $where_values[] = $criteria['marriage_date'];
        }

        if (!empty($criteria['marriage_place'])) {
            $where_conditions[] = "marriage_place LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($criteria['marriage_place']) . '%';
        }

        if (!empty($criteria['file_id'])) {
            $where_conditions[] = "file_id = %s";
            $where_values[] = $criteria['file_id'];
        }

        // Build ORDER BY clause
        $order_by = !empty($criteria['order_by']) ? $criteria['order_by'] : 'husband_name, wife_name';
        $order_direction = !empty($criteria['order_direction']) && strtoupper($criteria['order_direction']) === 'DESC' ? 'DESC' : 'ASC';

        // Build the query
        $where_clause = implode(' AND ', $where_conditions);
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$order_by} {$order_direction}";

        if (!empty($where_values)) {
            $query = $this->wpdb->prepare($query, $where_values);
        }

        $results = $this->wpdb->get_results($query);

        if ($results === null) {
            return [];
        }

        return array_map([$this, 'to_model'], $results);
    }

    /**
     * Count families matching search criteria
     *
     * @param array $criteria Search criteria
     * @return int Number of matching families
     */
    public function search_count(array $criteria = []): int {
        $where_conditions = ['deleted_at IS NULL']; // Exclude soft-deleted
        $where_values = [];

        // Build WHERE clause based on criteria (same as search method)
        if (!empty($criteria['husband_name'])) {
            $where_conditions[] = "husband_name LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($criteria['husband_name']) . '%';
        }

        if (!empty($criteria['wife_name'])) {
            $where_conditions[] = "wife_name LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($criteria['wife_name']) . '%';
        }

        if (!empty($criteria['marriage_date'])) {
            $where_conditions[] = "marriage_date = %s";
            $where_values[] = $criteria['marriage_date'];
        }

        if (!empty($criteria['marriage_place'])) {
            $where_conditions[] = "marriage_place LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($criteria['marriage_place']) . '%';
        }

        if (!empty($criteria['file_id'])) {
            $where_conditions[] = "file_id = %s";
            $where_values[] = $criteria['file_id'];
        }

        // Build the query
        $where_clause = implode(' AND ', $where_conditions);
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";

        if (!empty($where_values)) {
            $query = $this->wpdb->prepare($query, $where_values);
        }

        return (int) $this->wpdb->get_var($query);
    }

    /**
     * Get families with pagination
     *
     * @param array $criteria Search criteria
     * @param int $page Page number (1-based)
     * @param int $per_page Items per page
     * @return array Array with 'families' and 'pagination' keys
     */
    public function get_paginated(array $criteria = [], int $page = 1, int $per_page = 20): array {
        $page = max(1, $page);
        $per_page = max(1, min(100, $per_page)); // Limit between 1 and 100
        $offset = ($page - 1) * $per_page;

        // Get total count
        $total = $this->search_count($criteria);
        $total_pages = ceil($total / $per_page);

        $where_conditions = ['deleted_at IS NULL']; // Exclude soft-deleted
        $where_values = [];

        // Build WHERE clause based on criteria (same as search method)
        if (!empty($criteria['husband_name'])) {
            $where_conditions[] = "husband_name LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($criteria['husband_name']) . '%';
        }

        if (!empty($criteria['wife_name'])) {
            $where_conditions[] = "wife_name LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($criteria['wife_name']) . '%';
        }

        if (!empty($criteria['marriage_date'])) {
            $where_conditions[] = "marriage_date = %s";
            $where_values[] = $criteria['marriage_date'];
        }

        if (!empty($criteria['marriage_place'])) {
            $where_conditions[] = "marriage_place LIKE %s";
            $where_values[] = '%' . $this->wpdb->esc_like($criteria['marriage_place']) . '%';
        }

        if (!empty($criteria['file_id'])) {
            $where_conditions[] = "file_id = %s";
            $where_values[] = $criteria['file_id'];
        }

        // Build ORDER BY clause
        $order_by = !empty($criteria['order_by']) ? $criteria['order_by'] : 'husband_name, wife_name';
        $order_direction = !empty($criteria['order_direction']) && strtoupper($criteria['order_direction']) === 'DESC' ? 'DESC' : 'ASC';

        // Build the query with LIMIT
        $where_clause = implode(' AND ', $where_conditions);
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$order_by} {$order_direction} LIMIT %d OFFSET %d";

        // Add LIMIT values to the prepared statement values
        $where_values[] = $per_page;
        $where_values[] = $offset;

        $query = $this->wpdb->prepare($query, $where_values);
        $results = $this->wpdb->get_results($query);

        if ($results === null) {
            $results = [];
        }

        $families = array_map([$this, 'to_model'], $results);

        return [
            'families' => $families,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => $total_pages,
                'has_previous' => $page > 1,
                'has_next' => $page < $total_pages
            ]
        ];
    }

    /**
     * Get families by individual ID (either as husband or wife)
     *
     * @param int $individual_id The individual's ID
     * @return array Array of Family_Model objects
     */
    public function get_by_individual_id(int $individual_id): array {
        if ($individual_id <= 0) {
            return [];
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE (husband_id = %d OR wife_id = %d) AND deleted_at IS NULL 
             ORDER BY marriage_date ASC",
            $individual_id,
            $individual_id
        );

        $results = $this->wpdb->get_results($query);

        if ($results === null) {
            return [];
        }

        return array_map([$this, 'to_model'], $results);
    }
}
