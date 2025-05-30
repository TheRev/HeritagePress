<?php
/**
 * Individual Model Class
 *
 * @package HeritagePress\Models
 */

namespace HeritagePress\Models;

class Individual_Model implements Model_Interface {

    public ?int $id = null;
    public ?string $uuid = null;
    public ?string $file_id = null;
    public ?string $given_names = null;
    public ?string $surname = null;
    public ?string $title = null;
    public ?string $sex = null;
    public ?string $birth_date = null;
    public ?int $birth_place_id = null;
    public ?string $death_date = null;
    public ?int $death_place_id = null;
    public ?string $cause_of_death = null;
    public ?string $burial_date = null;
    public ?int $burial_location_id = null;
    public ?int $main_media_id = null;
    public ?string $occupation = null;
    public ?string $education = null;
    public ?string $religion = null;
    public ?string $nationality = null;
    public ?int $ancestor_interest = 0;
    public ?int $descendant_interest = 0;
    public ?string $user_reference_text = null;
    public ?string $restriction_type = null;
    public ?string $notes = null;
    public ?int $shared_note_id = null;
    public ?int $privacy = 0;
    public string $status = 'active';
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    private static string $audit_table_name = 'individuals';

    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }    public function get_id(): ?int {
        return $this->id;
    }

    public function get_uuid(): ?string {
        return $this->uuid;
    }

    public function get_file_id(): ?string {
        return $this->file_id;
    }

    public function get_given_names(): ?string {
        return $this->given_names;
    }

    public function get_surname(): ?string {
        return $this->surname;
    }

    public function get_title(): ?string {
        return $this->title;
    }

    public function get_sex(): ?string {
        return $this->sex;
    }

    public function get_birth_date(): ?string {
        return $this->birth_date;
    }

    public function get_birth_place(): ?string {
        // For now, return null since place names aren't implemented yet
        return null;
    }

    public function get_death_date(): ?string {
        return $this->death_date;
    }

    public function get_death_place(): ?string {
        // For now, return null since place names aren't implemented yet
        return null;
    }

    public function get_living_status(): string {
        // Determine living status based on death date
        if (!empty($this->death_date)) {
            return 'deceased';
        }
        // For now, default to unknown unless explicitly set
        return 'unknown';
    }

    public function get_notes(): ?string {
        return $this->notes;
    }

    public function get_created_at(): ?string {
        return $this->created_at;
    }

    public function get_updated_at(): ?string {
        return $this->updated_at;
    }

    public function get_table_name_for_audit(): string {
        return self::$audit_table_name;
    }

    public function toArray(): array {
        return get_object_vars($this);
    }

    public static function from_db_object(\stdClass $db_object): self {
        $data = (array) $db_object;
        return new self($data);
    }
}
