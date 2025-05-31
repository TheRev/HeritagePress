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

    // Cached relationship data
    private $families_as_child = null;
    private $families_as_parent = null;
    private $birth_place = null;
    private $death_place = null;
    private $events = null;
    private $citations = null;

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

    /**
     * Get all families where this individual is a child
     *
     * @return array Array of Family_Model objects
     */
    public function getFamiliesAsChild(): array {
        if ($this->families_as_child !== null) {
            return $this->families_as_child;
        }

        // Lazy load from repository
        $families = [];
        if ($this->id) {
            global $wpdb;
            $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
            $relationship_repo = new \HeritagePress\Repositories\Family_Relationship_Repository($audit_observer);
            $family_repo = new \HeritagePress\Repositories\Family_Repository($audit_observer);
            
            $relationships = $relationship_repo->get_families_as_child($this->id, $this->file_id);
            
            foreach ($relationships as $relationship) {
                $family = $family_repo->get_by_id($relationship->family_id);
                if ($family) {
                    $families[] = $family;
                }
            }
        }
        
        $this->families_as_child = $families;
        return $families;
    }

    /**
     * Get all families where this individual is a parent
     *
     * @return array Array of Family_Model objects
     */
    public function getFamiliesAsParent(): array {
        if ($this->families_as_parent !== null) {
            return $this->families_as_parent;
        }

        // Lazy load from repository
        $families = [];
        if ($this->id) {
            global $wpdb;
            $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
            $relationship_repo = new \HeritagePress\Repositories\Family_Relationship_Repository($audit_observer);
            $family_repo = new \HeritagePress\Repositories\Family_Repository($audit_observer);
            
            $relationships = $relationship_repo->get_families_as_parent($this->id, $this->file_id);
            
            foreach ($relationships as $relationship) {
                $family = $family_repo->get_by_id($relationship->family_id);
                if ($family) {
                    // Add relationship type to the family object
                    $family->relationship_type = $relationship->relationship_type;
                    $families[] = $family;
                }
            }
        }
        
        $this->families_as_parent = $families;
        return $families;
    }
    
    /**
     * Get the family where this individual is a spouse (first family found)
     *
     * @return Family_Model|null The family model or null if not found
     */
    public function getFamilyAsSpouse(): ?Family_Model {
        $families = $this->getFamiliesAsParent();
        return !empty($families) ? $families[0] : null;
    }
    
    /**
     * Get this individual's parents
     *
     * @param string $pedigree_type Optional filter by pedigree type (birth, adoption, foster, etc)
     * @return array Array of Individual_Model objects representing the parents
     */
    public function getParents(string $pedigree_type = null): array {
        $families = $this->getFamiliesAsChild();
        
        if (empty($families)) {
            return [];
        }
        
        // Use the first family by default (could be enhanced to support multiple families)
        $primary_family = $families[0];
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $relationship_repo = new \HeritagePress\Repositories\Family_Relationship_Repository($audit_observer);
        $individual_repo = new \HeritagePress\Repositories\Individual_Repository($audit_observer);
        
        $parent_relationships = $relationship_repo->get_parents_by_family($primary_family->id, $this->file_id);
        
        if (empty($parent_relationships)) {
            return [];
        }
        
        $parents = [];
        foreach ($parent_relationships as $relationship) {
            // Filter by pedigree type if specified
            if ($pedigree_type !== null && $relationship->pedigree_type !== $pedigree_type) {
                continue;
            }
            
            $parent = $individual_repo->get_by_id($relationship->individual_id);
            if ($parent) {
                $parent->relationship_type = $relationship->relationship_type;
                $parent->pedigree_type = $relationship->pedigree_type;
                $parents[] = $parent;
            }
        }
        
        return $parents;
    }
    
    /**
     * Get this individual's children
     *
     * @param int|null $family_id Optional family ID to filter by specific family
     * @return array Array of Individual_Model objects representing the children
     */
    public function getChildren(?int $family_id = null): array {
        $families = $this->getFamiliesAsParent();
        
        if (empty($families)) {
            return [];
        }
        
        $children = [];
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $relationship_repo = new \HeritagePress\Repositories\Family_Relationship_Repository($audit_observer);
        $individual_repo = new \HeritagePress\Repositories\Individual_Repository($audit_observer);
        
        foreach ($families as $family) {
            // If a specific family is requested, skip other families
            if ($family_id !== null && $family->id !== $family_id) {
                continue;
            }
            
            $child_relationships = $relationship_repo->get_children_by_family($family->id, $this->file_id);
            
            foreach ($child_relationships as $relationship) {
                $child = $individual_repo->get_by_id($relationship->individual_id);
                if ($child) {
                    $child->relationship_type = 'child';
                    $child->pedigree_type = $relationship->pedigree_type;
                    $child->birth_order = $relationship->birth_order;
                    $child->family_id = $family->id;
                    $children[] = $child;
                }
            }
        }
        
        // Sort children by birth order, then by birth date
        usort($children, function($a, $b) {
            // First sort by birth order if available
            if ($a->birth_order !== null && $b->birth_order !== null) {
                return $a->birth_order - $b->birth_order;
            }
            
            // Then try to sort by birth date
            if ($a->birth_date && $b->birth_date) {
                return strtotime($a->birth_date) - strtotime($b->birth_date);
            }
            
            // Default to ID order if no other sorting is possible
            return $a->id - $b->id;
        });
        
        return $children;
    }
    
    /**
     * Get this individual's siblings (including half-siblings)
     *
     * @param bool $include_half_siblings Whether to include half-siblings
     * @return array Array of Individual_Model objects representing the siblings
     */
    public function getSiblings(bool $include_half_siblings = true): array {
        $families = $this->getFamiliesAsChild();
        
        if (empty($families)) {
            return [];
        }
        
        $siblings = [];
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $relationship_repo = new \HeritagePress\Repositories\Family_Relationship_Repository($audit_observer);
        $individual_repo = new \HeritagePress\Repositories\Individual_Repository($audit_observer);
        
        foreach ($families as $family) {
            $child_relationships = $relationship_repo->get_children_by_family($family->id, $this->file_id);
            
            foreach ($child_relationships as $relationship) {
                // Skip self
                if ($relationship->individual_id === $this->id) {
                    continue;
                }
                
                $sibling = $individual_repo->get_by_id($relationship->individual_id);
                if ($sibling) {
                    $sibling->family_id = $family->id;
                    $sibling->pedigree_type = $relationship->pedigree_type;
                    $siblings[] = $sibling;
                }
            }
        }
        
        // Sort siblings by birth date
        usort($siblings, function($a, $b) {
            if ($a->birth_date && $b->birth_date) {
                return strtotime($a->birth_date) - strtotime($b->birth_date);
            }
            return $a->id - $b->id;
        });
        
        return $siblings;
    }
    
    /**
     * Get the birth place
     *
     * @return Place_Model|null The birth place or null if not set
     */
    public function getBirthPlace(): ?Place_Model {
        if ($this->birth_place !== null) {
            return $this->birth_place;
        }
        
        if (!$this->birth_place_id) {
            return null;
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $place_repo = new \HeritagePress\Repositories\Place_Repository($audit_observer);
        
        $this->birth_place = $place_repo->get_by_id($this->birth_place_id);
        return $this->birth_place;
    }
    
    /**
     * Get the death place
     *
     * @return Place_Model|null The death place or null if not set
     */
    public function getDeathPlace(): ?Place_Model {
        if ($this->death_place !== null) {
            return $this->death_place;
        }
        
        if (!$this->death_place_id) {
            return null;
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $place_repo = new \HeritagePress\Repositories\Place_Repository($audit_observer);
        
        $this->death_place = $place_repo->get_by_id($this->death_place_id);
        return $this->death_place;
    }
    
    /**
     * Get events for this individual
     *
     * @return array Array of Event_Model objects
     */
    public function getEvents(): array {
        if ($this->events !== null) {
            return $this->events;
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $event_repo = new \HeritagePress\Repositories\Event_Repository($audit_observer);
        
        $this->events = $event_repo->get_by_individual_id($this->id, $this->file_id);
        return $this->events;
    }
    
    /**
     * Get citations for this individual
     *
     * @return array Array of Citation_Model objects
     */
    public function getCitations(): array {
        if ($this->citations !== null) {
            return $this->citations;
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $citation_repo = new \HeritagePress\Repositories\Citation_Repository($audit_observer);
        
        $this->citations = $citation_repo->get_by_individual_id($this->id, $this->file_id);
        return $this->citations;
    }
    
    /**
     * Get the full name of this individual
     * 
     * @param bool $include_title Whether to include the title
     * @return string The full name
     */
    public function getFullName(bool $include_title = false): string {
        $name_parts = [];
        
        if ($include_title && $this->title) {
            $name_parts[] = $this->title;
        }
        
        if ($this->given_names) {
            $name_parts[] = $this->given_names;
        }
        
        if ($this->surname) {
            $name_parts[] = $this->surname;
        }
        
        return implode(' ', $name_parts);
    }
}
