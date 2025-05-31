<?php
/**
 * Model Interface
 *
 * @package HeritagePress\Models
 */

namespace HeritagePress\Models;

interface Model_Interface {
    /**
     * Gets the primary ID of the model.
     *
     * @return int|null
     */
    public function get_id();

    /**
     * Gets the UUID of the model.
     *
     * @return string|null
     */
    public function get_uuid();

    /**
     * Gets the File ID associated with the model (if applicable).
     *
     * @return string|null
     */
    public function get_file_id();

    /**
     * Gets the table name for audit logging purposes.
     *
     * @return string
     */
    public function get_table_name_for_audit(): string;

    /**
     * Converts the model to an array.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Creates a model instance from a database object (e.g., stdClass).
     *
     * @param \stdClass $db_object The database object.
     * @return static
     */
    public static function from_db_object(\stdClass $db_object): self;
}
