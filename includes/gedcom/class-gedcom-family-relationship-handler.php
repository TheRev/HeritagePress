<?php
/**
 * Family Relationship Handler Class for GEDCOM Processing
 * 
 * Handles parsing and processing family relationships from GEDCOM data.
 *
 * @package HeritagePress\GEDCOM
 */

namespace HeritagePress\GEDCOM;

class GedcomFamilyRelationshipHandler {
    
    private $file_id;
    private $individual_ids = [];
    private $family_ids = [];
    private $relationships = [];
    
    /**
     * Constructor
     *
     * @param string $file_id The current file/tree ID being processed
     */
    public function __construct(string $file_id) {
        $this->file_id = $file_id;
    }
    
    /**
     * Process an individual record and extract family references
     *
     * @param array $individual_data The individual record data
     * @param string $individual_id The GEDCOM ID of the individual
     * @param int $db_id The database ID of the saved individual
     */
    public function processIndividual(array $individual_data, string $individual_id, int $db_id) {
        // Store the mapping from GEDCOM ID to database ID
        $this->individual_ids[$individual_id] = $db_id;
        
        // Extract FAMS (family where individual is a spouse) links
        foreach ($individual_data as $field) {
            if ($field['tag'] === 'FAMS' && !empty($field['value'])) {
                $family_id = trim($field['value'], '@');
                
                // Determine relationship type based on sex
                $relationship_type = 'partner'; // Default
                foreach ($individual_data as $attribute) {
                    if ($attribute['tag'] === 'SEX') {
                        if ($attribute['value'] === 'M') {
                            $relationship_type = 'husband';
                        } elseif ($attribute['value'] === 'F') {
                            $relationship_type = 'wife';
                        }
                        break;
                    }
                }
                
                // Store the relationship for later processing
                $this->relationships[] = [
                    'type' => 'parent',
                    'individual_gedcom_id' => $individual_id,
                    'family_gedcom_id' => $family_id,
                    'relationship_type' => $relationship_type,
                    'pedigree_type' => 'birth' // Default for parent relationships
                ];
            }
            
            // Extract FAMC (family where individual is a child) links
            if ($field['tag'] === 'FAMC' && !empty($field['value'])) {
                $family_id = trim($field['value'], '@');
                $pedigree_type = 'birth'; // Default
                
                // Check for adoption or other relationship types
                if (isset($field['children'])) {
                    foreach ($field['children'] as $child_field) {
                        if ($child_field['tag'] === 'PEDI') {
                            $pedigree_type = strtolower($child_field['value']);
                        }
                    }
                }
                
                // Store the relationship for later processing
                $this->relationships[] = [
                    'type' => 'child',
                    'individual_gedcom_id' => $individual_id,
                    'family_gedcom_id' => $family_id,
                    'relationship_type' => 'child',
                    'pedigree_type' => $pedigree_type
                ];
            }
        }
    }
    
    /**
     * Process a family record and store the database ID
     *
     * @param string $family_id The GEDCOM ID of the family
     * @param int $db_id The database ID of the saved family
     */
    public function processFamily(string $family_id, int $db_id) {
        $this->family_ids[$family_id] = $db_id;
    }
    
    /**
     * Create all family relationships in the database
     *
     * @return array Statistics about the created relationships
     */
    public function createRelationships() {
        global $wpdb;
        $audit_observer = new \HeritagePress\Core\Audit_Log_Observer($wpdb, $wpdb->prefix . 'heritage_press_audit_logs');
        $relationship_repo = new \HeritagePress\Repositories\Family_Relationship_Repository($audit_observer);
        
        $stats = [
            'total' => count($this->relationships),
            'created' => 0,
            'skipped' => 0,
            'errors' => 0
        ];
        
        foreach ($this->relationships as $relationship) {
            // Skip if we don't have the necessary IDs
            if (!isset($this->individual_ids[$relationship['individual_gedcom_id']]) || 
                !isset($this->family_ids[$relationship['family_gedcom_id']])) {
                $stats['skipped']++;
                continue;
            }
            
            // Create the relationship record
            $relationship_data = [
                'uuid' => wp_generate_uuid4(),
                'file_id' => $this->file_id,
                'individual_id' => $this->individual_ids[$relationship['individual_gedcom_id']],
                'family_id' => $this->family_ids[$relationship['family_gedcom_id']],
                'relationship_type' => $relationship['relationship_type'],
                'pedigree_type' => $relationship['pedigree_type'],
                'is_current' => true
            ];
            
            // Add birth order for children based on birth dates if available
            if ($relationship['relationship_type'] === 'child') {
                // Birth order logic could be improved in a real implementation
                $relationship_data['birth_order'] = null;
            }
            
            $result = $relationship_repo->create($relationship_data);
            
            if ($result) {
                $stats['created']++;
            } else {
                $stats['errors']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get all relationships processed so far
     *
     * @return array The relationships array
     */
    public function getRelationships() {
        return $this->relationships;
    }
}
