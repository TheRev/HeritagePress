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

    // Cached relationship data
    private $husband = null;
    private $wife = null;
    private $parents = null;
    private $children = null;
    private $relationships = null;
    private $marriage_place = null;
    private $divorce_place = null;
    private $events = null;

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

    /**
     * Get the husband/partner in this family unit
     *
     * @return Individual_Model|null The individual or null if not available
     */
    public function getHusband(): ?Individual_Model {
        if ($this->husband !== null) {
            return $this->husband;
        }
        
        if (!$this->husband_id) {
            // Check for relationship-based connections
            return $this->getPartnerByType('husband');
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $individual_repo = new \HeritagePress\Repositories\Individual_Repository($audit_observer);
        
        $this->husband = $individual_repo->get_by_id($this->husband_id);
        return $this->husband;
    }
    
    /**
     * Get the wife/partner in this family unit
     *
     * @return Individual_Model|null The individual or null if not available
     */
    public function getWife(): ?Individual_Model {
        if ($this->wife !== null) {
            return $this->wife;
        }
        
        if (!$this->wife_id) {
            // Check for relationship-based connections
            return $this->getPartnerByType('wife');
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $individual_repo = new \HeritagePress\Repositories\Individual_Repository($audit_observer);
        
        $this->wife = $individual_repo->get_by_id($this->wife_id);
        return $this->wife;
    }
    
    /**
     * Get a partner by relationship type
     *
     * @param string $relationship_type The relationship type ('husband', 'wife', 'partner')
     * @return Individual_Model|null The individual or null if not found
     */
    private function getPartnerByType(string $relationship_type): ?Individual_Model {
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $relationship_repo = new \HeritagePress\Repositories\Family_Relationship_Repository($audit_observer);
        $individual_repo = new \HeritagePress\Repositories\Individual_Repository($audit_observer);
        
        $parents = $relationship_repo->get_parents_by_family($this->id, $this->file_id);
        
        foreach ($parents as $relationship) {
            if ($relationship->relationship_type === $relationship_type) {
                $partner = $individual_repo->get_by_id($relationship->individual_id);
                if ($partner) {
                    $partner->relationship_type = $relationship_type;
                    $partner->pedigree_type = $relationship->pedigree_type;
                    return $partner;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get all parents in this family (typically two, but could be more or less)
     *
     * @return array Array of Individual_Model objects
     */
    public function getParents(): array {
        if ($this->parents !== null) {
            return $this->parents;
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $relationship_repo = new \HeritagePress\Repositories\Family_Relationship_Repository($audit_observer);
        $individual_repo = new \HeritagePress\Repositories\Individual_Repository($audit_observer);
        
        $parent_relationships = $relationship_repo->get_parents_by_family($this->id, $this->file_id);
        
        $parents = [];
        foreach ($parent_relationships as $relationship) {
            $parent = $individual_repo->get_by_id($relationship->individual_id);
            if ($parent) {
                $parent->relationship_type = $relationship->relationship_type;
                $parent->pedigree_type = $relationship->pedigree_type;
                $parents[] = $parent;
            }
        }
        
        // If no relationships exist but husband_id or wife_id are set, use those
        if (empty($parents)) {
            if ($this->husband_id) {
                $husband = $individual_repo->get_by_id($this->husband_id);
                if ($husband) {
                    $husband->relationship_type = 'husband';
                    $parents[] = $husband;
                }
            }
            
            if ($this->wife_id) {
                $wife = $individual_repo->get_by_id($this->wife_id);
                if ($wife) {
                    $wife->relationship_type = 'wife';
                    $parents[] = $wife;
                }
            }
        }
        
        $this->parents = $parents;
        return $parents;
    }
    
    /**
     * Get the children of this family
     *
     * @param string|null $pedigree_type Optional filter by pedigree type
     * @return array Array of Individual_Model objects
     */
    public function getChildren(?string $pedigree_type = null): array {
        if ($this->children !== null && $pedigree_type === null) {
            return $this->children;
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $relationship_repo = new \HeritagePress\Repositories\Family_Relationship_Repository($audit_observer);
        $individual_repo = new \HeritagePress\Repositories\Individual_Repository($audit_observer);
        
        $child_relationships = $relationship_repo->get_children_by_family($this->id, $this->file_id);
        
        $children = [];
        foreach ($child_relationships as $relationship) {
            // Filter by pedigree type if specified
            if ($pedigree_type !== null && $relationship->pedigree_type !== $pedigree_type) {
                continue;
            }
            
            $child = $individual_repo->get_by_id($relationship->individual_id);
            if ($child) {
                $child->relationship_type = 'child';
                $child->pedigree_type = $relationship->pedigree_type;
                $child->birth_order = $relationship->birth_order;
                $children[] = $child;
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
        
        if ($pedigree_type === null) {
            $this->children = $children;
        }
        
        return $children;
    }
    
    /**
     * Get biological children
     *
     * @return array Array of Individual_Model objects
     */
    public function getBiologicalChildren(): array {
        return $this->getChildren('birth');
    }
    
    /**
     * Get adopted children
     *
     * @return array Array of Individual_Model objects
     */
    public function getAdoptedChildren(): array {
        return $this->getChildren('adoption');
    }
    
    /**
     * Get foster children
     *
     * @return array Array of Individual_Model objects
     */
    public function getFosterChildren(): array {
        return $this->getChildren('foster');
    }
    
    /**
     * Get all relationships connected to this family
     *
     * @return array Array of Family_Relationship_Model objects
     */
    public function getRelationships(): array {
        if ($this->relationships !== null) {
            return $this->relationships;
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $relationship_repo = new \HeritagePress\Repositories\Family_Relationship_Repository($audit_observer);
        
        $parent_relationships = $relationship_repo->get_parents_by_family($this->id, $this->file_id);
        $child_relationships = $relationship_repo->get_children_by_family($this->id, $this->file_id);
        
        $this->relationships = array_merge($parent_relationships, $child_relationships);
        return $this->relationships;
    }
    
    /**
     * Get the marriage place
     *
     * @return Place_Model|null The place model or null if not available
     */
    public function getMarriagePlace(): ?Place_Model {
        if ($this->marriage_place !== null) {
            return $this->marriage_place;
        }
        
        if (!$this->marriage_place_id) {
            return null;
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $place_repo = new \HeritagePress\Repositories\Place_Repository($audit_observer);
        
        $this->marriage_place = $place_repo->get_by_id($this->marriage_place_id);
        return $this->marriage_place;
    }
    
    /**
     * Get the divorce place
     *
     * @return Place_Model|null The place model or null if not available
     */
    public function getDivorcePlace(): ?Place_Model {
        if ($this->divorce_place !== null) {
            return $this->divorce_place;
        }
        
        if (!$this->divorce_place_id) {
            return null;
        }
        
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, 'audit_logs');
        $place_repo = new \HeritagePress\Repositories\Place_Repository($audit_observer);
        
        $this->divorce_place = $place_repo->get_by_id($this->divorce_place_id);
        return $this->divorce_place;
    }
    
    /**
     * Get events for this family
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
        
        $this->events = $event_repo->get_by_family_id($this->id, $this->file_id);
        return $this->events;
    }
    
    /**
     * Get a string representation of the family
     * 
     * @return string The family representation
     */
    public function getFamilyName(): string {
        $parents = $this->getParents();
        $parent_names = [];
        
        foreach ($parents as $parent) {
            $parent_names[] = $parent->getFullName();
        }
        
        if (!empty($parent_names)) {
            return implode(' & ', $parent_names) . ' Family';
        }
        
        return 'Family #' . $this->id;
    }
    
    /**
     * Get marriage date in a readable format
     * 
     * @return string|null The formatted marriage date or null
     */
    public function getFormattedMarriageDate(): ?string {
        if (!$this->marriage_date) {
            return null;
        }
        
        return date('F j, Y', strtotime($this->marriage_date));
    }
    
    /**
     * Get divorce date in a readable format
     * 
     * @return string|null The formatted divorce date or null
     */
    public function getFormattedDivorceDate(): ?string {
        if (!$this->divorce_date) {
            return null;
        }
        
        return date('F j, Y', strtotime($this->divorce_date));
    }
}
