<?php
/**
 * Family Model Class
 *
 * @package HeritagePress\Models
 */

namespace HeritagePress\Models;

class Family_Model implements Model_Interface {

    public ?int $id = null;
    public ?string $uuid = null;
    public ?string $file_id = null;
    public ?int $husband_id = null;
    public ?int $wife_id = null;
    public ?string $marriage_date = null;
    public ?int $marriage_place_id = null;
    public ?string $divorce_date = null;
    public ?int $divorce_place_id = null;
    public ?string $user_reference_text = null;
    public ?string $restriction_type = null;
    public ?string $notes = null;
    public ?int $shared_note_id = null;
    public ?int $privacy = 0;
    public string $status = 'active';
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    private static string $audit_table_name = 'families';

    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_uuid(): ?string {
        return $this->uuid;
    }

    public function get_file_id(): ?string {
        return $this->file_id;
    }

    public function get_table_name_for_audit(): string {
        return self::$audit_table_name;
    }

    public function toArray(): array {
        // Ensure all public properties are included, even if null
        $all_properties = get_class_vars(get_class($this));
        $data = [];
        foreach (array_keys($all_properties) as $property) {
            if (property_exists($this, $property) && !in_array($property, ['audit_table_name'])) { // Exclude static private property
                 $data[$property] = $this->{$property};
            }
        }
        return $data;
    }

    public static function from_db_object(\stdClass $db_object): self {
        $data = (array) $db_object;
        return new self($data);
    }
}
