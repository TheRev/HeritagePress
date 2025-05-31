<?php
/**
 * GedcomTree Repository Class
 *
 * @package HeritagePress
 */

namespace HeritagePress\Repositories;

use HeritagePress\Database\DatabaseManager;

class GedcomTree_Repository {

    private $wpdb;
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = DatabaseManager::get_table_prefix() . 'gedcom_trees';
    }

    /**
     * Create a new GEDCOM tree record.
     *
     * @param array $data Associative array of data for the new tree.
     *                    Expected keys: 'tree_id' (UUID), 'file_name'. Others are optional.
     * @return int|false The ID of the newly created tree on success, false on failure.
     */
    public function create(array $data) {
        if (empty($data['tree_id']) || empty($data['file_name'])) {
            // tree_id (uuid) and file_name are mandatory
            return false;
        }

        $defaults = [
            'title' => null,
            'description' => null,
            'character_set' => null,
            'gedcom_version' => null,
            'gedcom_form' => null,
            'source_product_id' => null,
            'source_product_version' => null,
            'source_product_name' => null,
            'source_product_corp' => null,
            'source_database_name' => null,
            'source_database_date' => null,
            'primary_submitter_id' => null,
            'destination_system_id' => null,
            'default_place_format_template' => null,
            'default_language_code' => null,
            'version' => 1,
            'status' => 'active',
            'meta' => null,
            // upload_date and last_updated are handled by the database
        ];

        $data = wp_parse_args($data, $defaults);

        // Validate ENUM fields if necessary
        if (isset($data['status']) && !in_array($data['status'], ['active', 'archived', null], true)) {
            $data['status'] = 'active'; // Default to active if invalid
        }

        $result = $this->wpdb->insert($this->table_name, $data);

        if ($result === false) {
            // Log error: $this->wpdb->last_error
            return false;
        }
        return $this->wpdb->insert_id;
    }

    /**
     * Retrieve a GEDCOM tree by its primary ID.
     *
     * @param int $id The ID of the tree.
     * @return object|null The tree object on success, null if not found or on error.
     */
    public function get_by_id(int $id) {
        if ($id <= 0) {
            return null;
        }
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id));
    }

    /**
     * Retrieve a GEDCOM tree by its tree_id (UUID).
     *
     * @param string $tree_uuid The UUID of the tree.
     * @return object|null The tree object on success, null if not found or on error.
     */
    public function get_by_uuid(string $tree_uuid) {
        if (empty($tree_uuid)) {
            return null;
        }
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE tree_id = %s", $tree_uuid));
    }

    /**
     * Delete a GEDCOM tree record by its primary ID (hard delete).
     *
     * @param int $id The ID of the tree to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        // Before deleting a tree, consider implications: related data in other tables might need cleanup
        // (e.g., individuals, families, etc., linked by file_id which corresponds to tree_id).
        // This basic delete does not handle cascading deletes or orphaned record prevention in other tables.
        // A more robust solution would involve transaction management and deleting/unlinking related records.

        $result = $this->wpdb->delete($this->table_name, ['id' => $id]);

        if ($result === false) {
            // Log error: $this->wpdb->last_error
            return false;
        }
        return true; // Returns true if row was deleted or if row didn't exist and no error occurred.
                     // $wpdb->delete returns number of rows affected, so check could be $result > 0 for stricter success.
    }
    
    /**
     * Update an existing GEDCOM tree record by its primary ID.
     *
     * @param int $id The ID of the tree to update.
     * @param array $data Associative array of data to update.
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool {
        if ($id <= 0 || empty($data)) {
            return false;
        }

        unset($data['id']);
        unset($data['tree_id']); // tree_id (UUID) should generally not be changed after creation.

        if (isset($data['status']) && !in_array($data['status'], ['active', 'archived', null], true)) {
            unset($data['status']); // Remove if invalid, or handle as error
        }
        
        // Add last_updated timestamp automatically if not provided
        if (!isset($data['last_updated'])) {
            $data['last_updated'] = current_time('mysql', 1); // GMT timestamp
        }

        $result = $this->wpdb->update($this->table_name, $data, ['id' => $id]);

        if ($result === false) {
            // Log error: $this->wpdb->last_error
            return false;
        }
        return true;
    }
}
