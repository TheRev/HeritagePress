<?php
/**
 * Family Relationship Repository Class
 * 
 * Handles operations related to family relationships, such as parent-child connections.
 *
 * @package HeritagePress\Repositories
 */

namespace HeritagePress\Repositories;

use HeritagePress\Database\DatabaseManager;
use HeritagePress\Models\Family_Relationship_Model;
use HeritagePress\Core\Audit_Log_Observer;

class Family_Relationship_Repository {

    private $wpdb;
    private $table_name;
    private $audit_observer;

    /**
     * Constructor
     *
     * @param Audit_Log_Observer $audit_observer Instance of audit log observer
     */
    public function __construct(Audit_Log_Observer $audit_observer) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = DatabaseManager::get_table_prefix() . 'family_relationships';
        $this->audit_observer = $audit_observer;
    }

    /**
     * Converts a database row to a Family_Relationship_Model
     *
     * @param \stdClass|null $db_row The database row
     * @return Family_Relationship_Model|null The model or null if input is null
     */
    private function to_model(?\stdClass $db_row): ?Family_Relationship_Model {
        if (!$db_row) {
            return null;
        }
        return Family_Relationship_Model::from_db_object($db_row);
    }

    /**
     * Create a new family relationship record
     *
     * @param array $data Associative array of data for the new relationship
     * @return Family_Relationship_Model|false The created model on success, false on failure
     */
    public function create(array $data) {
        if (empty($data['uuid']) || empty($data['file_id']) || empty($data['individual_id']) || 
            empty($data['family_id']) || empty($data['relationship_type'])) {
            return false;
        }

        $defaults = [
            'pedigree_type' => 'birth',
            'birth_order' => null,
            'is_current' => true,
            'notes' => null,
            'shared_note_id' => null,
            'privacy' => 0,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'deleted_at' => null
        ];

        $data = array_merge($defaults, $data);

        $result = $this->wpdb->insert(
            $this->table_name,
            $data,
            $this->get_format()
        );

        if ($result === false) {
            return false;
        }

        $id = $this->wpdb->insert_id;
        $new_relationship = $this->get_by_id($id);

        if ($new_relationship) {
            // Log the creation event
            $this->audit_observer->log_creation('family_relationships', $id, $data['uuid'], $data['file_id']);
        }

        return $new_relationship;
    }

    /**
     * Get a family relationship by ID
     *
     * @param int $id The relationship ID
     * @return Family_Relationship_Model|null The model or null if not found
     */
    public function get_by_id(int $id): ?Family_Relationship_Model {
        $sql = "SELECT * FROM {$this->table_name} WHERE id = %d AND deleted_at IS NULL";
        $result = $this->wpdb->get_row($this->wpdb->prepare($sql, $id));
        return $this->to_model($result);
    }

    /**
     * Get a family relationship by UUID
     *
     * @param string $uuid The relationship UUID
     * @param string|null $file_id Optional file ID to restrict search
     * @return Family_Relationship_Model|null The model or null if not found
     */
    public function get_by_uuid(string $uuid, ?string $file_id = null): ?Family_Relationship_Model {
        $sql = "SELECT * FROM {$this->table_name} WHERE uuid = %s AND deleted_at IS NULL";
        $params = [$uuid];

        if ($file_id !== null) {
            $sql .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $result = $this->wpdb->get_row($this->wpdb->prepare($sql, $params));
        return $this->to_model($result);
    }

    /**
     * Get all children for a specific family
     *
     * @param int $family_id The family ID
     * @param string|null $file_id Optional file ID to restrict search
     * @return array Array of Family_Relationship_Model objects
     */
    public function get_children_by_family(int $family_id, ?string $file_id = null): array {
        $sql = "SELECT * FROM {$this->table_name} 
                WHERE family_id = %d 
                AND relationship_type = 'child' 
                AND deleted_at IS NULL";
        $params = [$family_id];

        if ($file_id !== null) {
            $sql .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $sql .= " ORDER BY birth_order ASC, id ASC";

        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        
        if (!$results) {
            return [];
        }

        $children = [];
        foreach ($results as $row) {
            $children[] = $this->to_model($row);
        }
        
        return $children;
    }

    /**
     * Get all parents for a specific family
     *
     * @param int $family_id The family ID
     * @param string|null $file_id Optional file ID to restrict search
     * @return array Array of Family_Relationship_Model objects
     */
    public function get_parents_by_family(int $family_id, ?string $file_id = null): array {
        $sql = "SELECT * FROM {$this->table_name} 
                WHERE family_id = %d 
                AND relationship_type IN ('husband', 'wife', 'partner') 
                AND deleted_at IS NULL";
        $params = [$family_id];

        if ($file_id !== null) {
            $sql .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        
        if (!$results) {
            return [];
        }

        $parents = [];
        foreach ($results as $row) {
            $parents[] = $this->to_model($row);
        }
        
        return $parents;
    }

    /**
     * Get all families for an individual as a child
     *
     * @param int $individual_id The individual ID
     * @param string|null $file_id Optional file ID to restrict search
     * @return array Array of Family_Relationship_Model objects
     */
    public function get_families_as_child(int $individual_id, ?string $file_id = null): array {
        $sql = "SELECT * FROM {$this->table_name} 
                WHERE individual_id = %d 
                AND relationship_type = 'child' 
                AND deleted_at IS NULL";
        $params = [$individual_id];

        if ($file_id !== null) {
            $sql .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        
        if (!$results) {
            return [];
        }

        $relationships = [];
        foreach ($results as $row) {
            $relationships[] = $this->to_model($row);
        }
        
        return $relationships;
    }

    /**
     * Get all families for an individual as a parent
     *
     * @param int $individual_id The individual ID
     * @param string|null $file_id Optional file ID to restrict search
     * @return array Array of Family_Relationship_Model objects
     */
    public function get_families_as_parent(int $individual_id, ?string $file_id = null): array {
        $sql = "SELECT * FROM {$this->table_name} 
                WHERE individual_id = %d 
                AND relationship_type IN ('husband', 'wife', 'partner') 
                AND deleted_at IS NULL";
        $params = [$individual_id];

        if ($file_id !== null) {
            $sql .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        
        if (!$results) {
            return [];
        }

        $relationships = [];
        foreach ($results as $row) {
            $relationships[] = $this->to_model($row);
        }
        
        return $relationships;
    }

    /**
     * Update a family relationship
     *
     * @param int $id The relationship ID
     * @param array $data The data to update
     * @return bool True on success, false on failure
     */
    public function update(int $id, array $data): bool {
        // Prevent changing core identifiers
        unset($data['id']);
        unset($data['uuid']);
        unset($data['file_id']);
        unset($data['created_at']);
        unset($data['deleted_at']);

        $data['updated_at'] = current_time('mysql');

        // Get the original record for audit purposes
        $original = $this->get_by_id($id);
        if (!$original) {
            return false;
        }

        $result = $this->wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            $this->get_format_for_data($data),
            ['%d']
        );

        if ($result !== false) {
            // Log the update
            $this->audit_observer->log_update(
                'family_relationships', 
                $id, 
                $original->uuid, 
                $original->file_id, 
                array_keys($data)
            );
            return true;
        }

        return false;
    }

    /**
     * Soft delete a family relationship
     *
     * @param int $id The relationship ID
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool {
        $relationship = $this->get_by_id($id);
        if (!$relationship) {
            return false;
        }

        $result = $this->wpdb->update(
            $this->table_name,
            ['deleted_at' => current_time('mysql')],
            ['id' => $id],
            ['%s'],
            ['%d']
        );

        if ($result !== false) {
            // Log the deletion
            $this->audit_observer->log_deletion(
                'family_relationships',
                $id,
                $relationship->uuid,
                $relationship->file_id
            );
            return true;
        }

        return false;
    }

    /**
     * Get column formats for database operations
     *
     * @return array Array of column formats
     */
    private function get_format(): array {
        return [
            'id' => '%d',
            'uuid' => '%s',
            'file_id' => '%s',
            'individual_id' => '%d',
            'family_id' => '%d',
            'relationship_type' => '%s',
            'pedigree_type' => '%s',
            'birth_order' => '%d',
            'is_current' => '%d',
            'notes' => '%s',
            'shared_note_id' => '%d',
            'privacy' => '%d',
            'status' => '%s',
            'created_at' => '%s',
            'updated_at' => '%s',
            'deleted_at' => '%s'
        ];
    }

    /**
     * Get formats for specific data array
     *
     * @param array $data The data array
     * @return array Array of formats
     */
    private function get_format_for_data(array $data): array {
        $all_formats = $this->get_format();
        $formats = [];
        
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $all_formats)) {
                $formats[] = $all_formats[$key];
            }
        }
        
        return $formats;
    }
}
