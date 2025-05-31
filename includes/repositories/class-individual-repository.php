<?php
/**
 * Individual Repository Class
 *
 * @package HeritagePress
 */

namespace HeritagePress\Repositories;

// Load WordPress compatibility if not in WordPress context
if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../wordpress-compatibility.php';
}

use HeritagePress\Database\DatabaseManager;
use HeritagePress\Core\Audit_Log_Observer;
use HeritagePress\Models\Individual_Model; // Added
use HeritagePress\Models\Model_Interface; // Added for type hinting if needed, though Individual_Model is concrete here

class Individual_Repository {
    
    /** @var wpdb */
    private $wpdb;
    private $table_name;
    private Audit_Log_Observer $audit_observer; // Added observer property

    /**
     * Constructor
     *
     * @param Audit_Log_Observer $audit_observer Instance of the audit log observer.
     */    public function __construct(Audit_Log_Observer $audit_observer) {
        global $wpdb;
        /** @var wpdb $wpdb */
        $this->wpdb = $wpdb;
        $this->table_name = DatabaseManager::get_table_prefix() . 'individuals';
        $this->audit_observer = $audit_observer;
    }

    /**
     * Create a new individual record.
     *
     * @param array $data Associative array of data for the new individual.
     * @return int|false The ID of the newly created individual on success, false on failure.
     */
    public function create(array $data) {
        if (empty($data['uuid']) || empty($data['file_id'])) {
            // UUID and file_id are mandatory
            return false;
        }

        // Ensure all required fields are present or have defaults
        $defaults = [
            'given_names' => null,
            'surname' => null,
            'title' => null,
            'sex' => null,
            'birth_date' => null,
            'birth_place_id' => null,
            'death_date' => null,
            'death_place_id' => null,
            'cause_of_death' => null,
            'burial_date' => null,
            'burial_location_id' => null,
            'main_media_id' => null,
            'occupation' => null,
            'education' => null,
            'religion' => null,
            'nationality' => null,
            'ancestor_interest' => 0,
            'descendant_interest' => 0,
            'user_reference_text' => null,
            'restriction_type' => null,
            'notes' => null,
            'shared_note_id' => null,
            'privacy' => 0,
            'status' => 'active',
            // created_at and updated_at are handled by the database
        ];

        $data = wp_parse_args($data, $defaults);

        // Validate ENUM/SET fields if necessary, or rely on DB constraints
        // For example, for 'sex':
        if (isset($data['sex']) && !in_array($data['sex'], ['M', 'F', 'X', 'U', null], true)) {
            // Handle invalid sex value, e.g., log error, return false, or set to null
            $data['sex'] = null; 
        }
        
        // For 'restriction_type':
        if (isset($data['restriction_type'])) {
            $valid_restrictions = ['CONFIDENTIAL', 'LOCKED', 'PRIVACY'];
            $input_restrictions = is_array($data['restriction_type']) ? $data['restriction_type'] : explode(',', $data['restriction_type']);
            $validated_restrictions = array_intersect($input_restrictions, $valid_restrictions);
            $data['restriction_type'] = !empty($validated_restrictions) ? implode(',', $validated_restrictions) : null;
        } else {
            $data['restriction_type'] = null;
        }


        $result = $this->wpdb->insert($this->table_name, $data);

        if ($result === false) {
            // Log error: $this->wpdb->last_error
            return false;
        }
        $id = $this->wpdb->insert_id;
        $created_individual_db_object = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id));
        if ($created_individual_db_object) {
            $created_model = Individual_Model::from_db_object($created_individual_db_object);
            $this->audit_observer->created($created_model);
        }
        return $id;
    }

    /**
     * Retrieve an individual by their primary ID.
     *
     * @param int $id The ID of the individual.
     * @return Individual_Model|null The individual model on success, null if not found or on error.
     */
    public function get_by_id(int $id): ?Individual_Model {
        if ($id <= 0) {
            return null;
        }
        $db_object = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d AND deleted_at IS NULL", $id));
        if ($db_object) {
            return Individual_Model::from_db_object($db_object);
        }
        return null;
    }

    /**
     * Retrieve an individual by their UUID.
     * Only returns non-deleted records.
     *
     * @param string $uuid The UUID of the individual.
     * @return Individual_Model|null The individual model on success, null if not found or on error.
     */
    public function get_by_uuid(string $uuid): ?Individual_Model {
        if (empty($uuid)) {
            return null;
        }
        $db_object = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE uuid = %s AND deleted_at IS NULL", $uuid));
        if ($db_object) {
            return Individual_Model::from_db_object($db_object);
        }
        return null;
    }

    /**
     * Update an existing individual record by its primary ID.
     *
     * @param int $id The ID of the individual to update.
     * @param array $data Associative array of data to update.
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool {
        if ($id <= 0 || empty($data)) {
            return false;
        }

        $original_model = $this->get_by_id($id); // Fetches as Individual_Model
        if (!$original_model) {
            return false; // Record to update not found
        }

        // Prevent changing uuid or file_id as they are immutable identifiers post-creation for a record.
        unset($data['uuid']);
        unset($data['file_id']);
        unset($data['id']); // Also ensure primary key is not in data array

        // Validate ENUM/SET fields if necessary
        if (isset($data['sex']) && !in_array($data['sex'], ['M', 'F', 'X', 'U', null], true)) {
            // Handle invalid sex value, e.g., log error or remove from update
            unset($data['sex']); 
        }

        if (isset($data['restriction_type'])) {
            $valid_restrictions = ['CONFIDENTIAL', 'LOCKED', 'PRIVACY'];
            $input_restrictions = is_array($data['restriction_type']) ? $data['restriction_type'] : explode(',', $data['restriction_type']);
            $validated_restrictions = array_intersect($input_restrictions, $valid_restrictions);
            $data['restriction_type'] = !empty($validated_restrictions) ? implode(',', $validated_restrictions) : null;
        } elseif (array_key_exists('restriction_type', $data) && $data['restriction_type'] === null) {
            // Allow explicitly setting to null
            $data['restriction_type'] = null;
        } else if (isset($data['restriction_type'])) {
             unset($data['restriction_type']); // remove if not valid and not explicitly null
        }

        // Add updated_at timestamp automatically if not provided
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql', 1); // GMT timestamp
        }

        // $this->audit_observer->updating($original_individual); // Call before update
        // For `updating` to be truly useful, the observer or model needs to handle diffs.
        // For now, we focus on `updated`.

        $result = $this->wpdb->update($this->table_name, $data, ['id' => $id]);

        if ($result === false) {
            return false;
        }

        $updated_model = $this->get_by_id($id); // Fetches as Individual_Model
        if ($updated_model) {
            $this->audit_observer->updated($original_model, $updated_model);
        }
        return true;
    }

    /**
     * Delete an individual record by its primary ID (soft delete).
     *
     * @param int $id The ID of the individual to soft delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        $model_to_delete = $this->get_by_id($id); // Fetches as Individual_Model
        if (!$model_to_delete) {
            // If record doesn't exist (or already soft-deleted), 
            // $wpdb->update will affect 0 rows. Current behavior is to return true.
            // No audit log if record not found for deleting.
            // Ensure we only attempt to update if not already soft-deleted to avoid issues with return value of update.
            $existing_record_check = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT id, deleted_at FROM {$this->table_name} WHERE id = %d", $id
            ));
            if (!$existing_record_check || $existing_record_check->deleted_at !== null) {
                return $existing_record_check && $existing_record_check->deleted_at !== null; // True if already deleted, false if not found
            }
            // If found and not deleted, proceed with update but no audit as it was not fetched by get_by_id()
             return (bool) $this->wpdb->update(
                $this->table_name,
                ['deleted_at' => current_time('mysql', 1)],
                ['id' => $id, 'deleted_at' => null], 
                ['%s'],
                ['%d', null]
            );
        }
        $this->audit_observer->deleting($model_to_delete);

        $result = $this->wpdb->update(
            $this->table_name,
            ['deleted_at' => current_time('mysql', 1)],
            ['id' => $id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            // Log error: $this->wpdb->last_error
            return false;
        }
        // No ->deleted call here as the record is now "gone" from standard queries.
        // The `deleting` event captures the state before it's marked as deleted.
        return true;
    }

    /**
     * Restore a soft-deleted individual record by its primary ID.
     *
     * @param int $id The ID of the individual to restore.
     * @return bool True on success, false on failure.
     */
    public function restore(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        // Fetch the record even if soft-deleted to ensure it exists before restore
        $db_object_to_restore = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id));
        if (!$db_object_to_restore || $db_object_to_restore->deleted_at === null) {
            return false; 
        }
        // $model_before_restore = Individual_Model::from_db_object($db_object_to_restore); // If needed for an observer hook before restore

        $result = $this->wpdb->update(
            $this->table_name,
            ['deleted_at' => null],
            ['id' => $id],
            [null],
            ['%d']
        );

        if ($result === false) {
            return false;
        }

        $restored_model = $this->get_by_id($id); // Fetches as Individual_Model
        if ($restored_model) {
            $this->audit_observer->restored($restored_model);
        }
        return true;
    }

    /**
     * Permanently delete an individual record by its primary ID.
     *
     * @param int $id The ID of the individual to permanently delete.
     * @return bool True on success, false on failure.
     */
    public function force_delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        // Fetch the record (even if soft-deleted) to log its state before permanent deletion
        $db_object_to_force_delete = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id));

        if ($db_object_to_force_delete) {
            $model_to_force_delete = Individual_Model::from_db_object($db_object_to_force_delete);
            $this->audit_observer->force_deleting($model_to_force_delete);
        }

        $result = $this->wpdb->delete($this->table_name, ['id' => $id], ['%d']);

        if ($result === false) {
            // Log error: $this->wpdb->last_error
            return false;
        }
        // No ->deleted call here as the record is physically gone.
        return true;
    }

    /**
     * Retrieve all individuals, including soft-deleted ones if specified.
     *
     * @param bool $include_deleted Whether to include soft-deleted records.
     * @return Individual_Model[] Array of individual models.
     */
    public function get_all(bool $include_deleted = false): array {
        $sql = "SELECT * FROM {$this->table_name}";
        if (!$include_deleted) {
            $sql .= " WHERE deleted_at IS NULL";
        }
        $db_objects = $this->wpdb->get_results($sql);
        $models = [];
        if ($db_objects) {
            foreach ($db_objects as $db_object) {
                $models[] = Individual_Model::from_db_object($db_object);
            }
        }
        return $models;
    }

    /**
     * Find individuals by surname.
     * Only returns non-deleted records.
     *
     * @param string $surname The surname to search for.
     * @return Individual_Model[] Array of individual models matching the surname.
     */
    public function find_by_surname(string $surname): array {
        if (empty($surname)) {
            return [];
        }
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE surname = %s AND deleted_at IS NULL",
            $surname
        );
        $db_objects = $this->wpdb->get_results($sql);
        $models = [];
        if ($db_objects) {
            foreach ($db_objects as $db_object) {
                $models[] = Individual_Model::from_db_object($db_object);
            }
        }
        return $models;
    }    /**
     * Search individuals by multiple criteria
     *
     * @param array $criteria Search criteria array
     * @param int $limit Maximum number of results to return
     * @param int $offset Offset for pagination
     * @return array Array of Individual_Model objects
     */
    public function search(array $criteria = [], int $limit = 10, int $offset = 0): array {
        $where_conditions = ["deleted_at IS NULL"];
        $params = [];

        // Build WHERE conditions based on criteria
        if (!empty($criteria['name'])) {
            $name = '%' . $criteria['name'] . '%';
            $where_conditions[] = "(given_names LIKE %s OR surname LIKE %s)";
            $params[] = $name;
            $params[] = $name;
        }

        if (!empty($criteria['given_names'])) {
            $where_conditions[] = "given_names LIKE %s";
            $params[] = '%' . $criteria['given_names'] . '%';
        }

        if (!empty($criteria['surname'])) {
            $where_conditions[] = "surname LIKE %s";
            $params[] = '%' . $criteria['surname'] . '%';
        }

        if (!empty($criteria['sex'])) {
            $where_conditions[] = "sex = %s";
            $params[] = $criteria['sex'];
        }

        if (!empty($criteria['birth_year'])) {
            $where_conditions[] = "YEAR(birth_date) = %d";
            $params[] = intval($criteria['birth_year']);
        }

        if (!empty($criteria['death_year'])) {
            $where_conditions[] = "YEAR(death_date) = %d";
            $params[] = intval($criteria['death_year']);
        }

        $where_clause = implode(' AND ', $where_conditions);
        
        // Add limit and offset
        $limit_clause = $this->wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);

        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY surname, given_names" . $limit_clause;
        
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }

        $db_objects = $this->wpdb->get_results($sql);
        $models = [];
        
        if ($db_objects) {
            foreach ($db_objects as $db_object) {
                $models[] = Individual_Model::from_db_object($db_object);
            }
        }
        
        return $models;
    }

    /**
     * Get total count of individuals matching search criteria
     *
     * @param array $criteria Search criteria array
     * @return int Total count
     */
    public function search_count(array $criteria = []): int {
        $where_conditions = ["deleted_at IS NULL"];
        $params = [];

        // Build WHERE conditions (same logic as search method)
        if (!empty($criteria['name'])) {
            $name = '%' . $criteria['name'] . '%';
            $where_conditions[] = "(given_names LIKE %s OR surname LIKE %s)";
            $params[] = $name;
            $params[] = $name;
        }

        if (!empty($criteria['given_names'])) {
            $where_conditions[] = "given_names LIKE %s";
            $params[] = '%' . $criteria['given_names'] . '%';
        }

        if (!empty($criteria['surname'])) {
            $where_conditions[] = "surname LIKE %s";
            $params[] = '%' . $criteria['surname'] . '%';
        }

        if (!empty($criteria['sex'])) {
            $where_conditions[] = "sex = %s";
            $params[] = $criteria['sex'];
        }

        if (!empty($criteria['birth_year'])) {
            $where_conditions[] = "YEAR(birth_date) = %d";
            $params[] = intval($criteria['birth_year']);
        }

        if (!empty($criteria['death_year'])) {
            $where_conditions[] = "YEAR(death_date) = %d";
            $params[] = intval($criteria['death_year']);
        }

        $where_clause = implode(' AND ', $where_conditions);
        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }

        return (int) $this->wpdb->get_var($sql);
    }

    /**
     * Get paginated list of all individuals
     *
     * @param int $page Page number (1-based)
     * @param int $per_page Number of items per page
     * @param bool $include_deleted Whether to include deleted records
     * @return array Array with 'items', 'total', 'pages' keys
     */
    public function get_paginated(int $page = 1, int $per_page = 20, bool $include_deleted = false): array {
        $offset = ($page - 1) * $per_page;
        
        $where_clause = $include_deleted ? "" : "WHERE deleted_at IS NULL";
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";
        $total = (int) $this->wpdb->get_var($count_sql);
        
        // Get items
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY surname, given_names LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );
        
        $db_objects = $this->wpdb->get_results($sql);
        $items = [];
        
        if ($db_objects) {
            foreach ($db_objects as $db_object) {
                $items[] = Individual_Model::from_db_object($db_object);
            }
        }
        
        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ];
    }

}
