<?php
/**
 * Family Relationship Model Class
 * 
 * Represents a relationship between an individual and a family, such as child-of-family
 * or parent-of-family relationships.
 *
 * @package HeritagePress\Models
 */

namespace HeritagePress\Models;

class Family_Relationship_Model implements Model_Interface {

    // Core properties
    public ?int $id = null;
    public ?string $uuid = null;
    public ?string $file_id = null;
    public ?int $individual_id = null; // The individual in this relationship
    public ?int $family_id = null; // The family this relationship belongs to
    public ?string $relationship_type = null; // e.g., 'child', 'husband', 'wife', 'partner'
    public ?string $pedigree_type = null; // e.g., 'birth', 'adoption', 'foster', 'sealing'
    
    // For child relationships
    public ?int $birth_order = null; // Birth order within family (1-based)
    
    // For partner relationships
    public ?bool $is_current = true; // Whether this is a current relationship
    
    // Common fields
    public ?string $notes = null;
    public ?int $shared_note_id = null;
    public ?int $privacy = 0;
    public string $status = 'active';
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    private static string $audit_table_name = 'family_relationships';

    /**
     * Constructor
     *
     * @param array $data Initial data for the model
     */
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Get the model ID
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Get the model UUID
     *
     * @return string|null
     */
    public function get_uuid(): ?string {
        return $this->uuid;
    }

    /**
     * Get the file ID
     *
     * @return string|null
     */
    public function get_file_id(): ?string {
        return $this->file_id;
    }

    /**
     * Get table name for audit purposes
     *
     * @return string
     */
    public function get_table_name_for_audit(): string {
        return self::$audit_table_name;
    }

    /**
     * Convert model to array
     *
     * @return array
     */
    public function toArray(): array {
        // Ensure all public properties are included, even if null
        $all_properties = get_class_vars(get_class($this));
        $data = [];
        foreach (array_keys($all_properties) as $property) {
            if (property_exists($this, $property) && !in_array($property, ['audit_table_name'])) {
                 $data[$property] = $this->{$property};
            }
        }
        return $data;
    }

    /**
     * Create model from database object
     *
     * @param \stdClass $db_object Database object
     * @return self
     */
    public static function from_db_object(\stdClass $db_object): self {
        $data = (array) $db_object;
        return new self($data);
    }

    /**
     * Check if this relationship is a child relationship
     * 
     * @return bool
     */
    public function is_child(): bool {
        return $this->relationship_type === 'child';
    }

    /**
     * Check if this relationship is a parent relationship
     * 
     * @return bool
     */
    public function is_parent(): bool {
        return in_array($this->relationship_type, ['husband', 'wife', 'partner']);
    }
    
    /**
     * Check if this is a biological relationship
     * 
     * @return bool
     */
    public function is_biological(): bool {
        return $this->pedigree_type === 'birth' || $this->pedigree_type === null;
    }
    
    /**
     * Check if this is an adoptive relationship
     * 
     * @return bool
     */
    public function is_adoptive(): bool {
        return $this->pedigree_type === 'adoption';
    }
}
