<?php
/**
 * GEDCOM Parser class for Heritage Press
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Heritage Press GEDCOM Parser Class
 */
class Heritage_Press_GEDCOM_Parser {
    
    /**
     * Parsed individuals
     */
    private $individuals = array();
    
    /**
     * Parsed families
     */
    private $families = array();
    
    /**
     * Parse errors
     */
    private $errors = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize parser
    }
      /**
     * Parse GEDCOM file
     *
     * @param string $file_path Path to GEDCOM file
     * @return array Parse results
     */
    public function parse_file($file_path) {
        if (!file_exists($file_path)) {
            $this->errors[] = 'File not found: ' . $file_path;
            return array(
                'individuals' => 0,
                'families' => 0,
                'errors' => $this->errors
            );
        }
        
        $content = file_get_contents($file_path);
        if ($content === false) {
            $this->errors[] = 'Unable to read file: ' . $file_path;
            return array(
                'individuals' => 0,
                'families' => 0,
                'errors' => $this->errors
            );
        }
        
        return $this->parse_content($content);
    }
    
    /**
     * Parse GEDCOM content
     *
     * @param string $content GEDCOM content
     * @return array Parse results
     */
    public function parse_content($content) {
        $lines = explode("\n", $content);
        $current_record = null;
        $current_level = 0;
        
        foreach ($lines as $line_number => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Parse GEDCOM line format: LEVEL TAG [VALUE]
            if (!preg_match('/^(\d+)\s+([A-Z_]+)(?:\s+(.*))?$/', $line, $matches)) {
                continue;
            }
            
            $level = (int) $matches[1];
            $tag = $matches[2];
            $value = isset($matches[3]) ? trim($matches[3]) : '';
            
            // Handle different record types
            if ($level === 0) {
                // Save previous record
                if ($current_record) {
                    $this->save_record($current_record);
                }
                
                // Start new record
                $current_record = $this->init_record($tag, $value);
                $current_level = 0;
            } else {
                // Add data to current record
                if ($current_record) {
                    $this->add_to_record($current_record, $level, $tag, $value);
                }
            }
        }
        
        // Save last record
        if ($current_record) {
            $this->save_record($current_record);
        }
        
        return array(
            'individuals' => count($this->individuals),
            'families' => count($this->families),
            'errors' => $this->errors
        );
    }
    
    /**
     * Initialize a new record
     *
     * @param string $tag Record tag
     * @param string $value Record value
     * @return array Record data
     */
    private function init_record($tag, $value) {
        $record = array(
            'type' => $tag,
            'gedcom_id' => $value,
            'data' => array()
        );
        
        return $record;
    }
    
    /**
     * Add data to record
     *
     * @param array &$record Record to add to
     * @param int $level GEDCOM level
     * @param string $tag GEDCOM tag
     * @param string $value GEDCOM value
     */
    private function add_to_record(&$record, $level, $tag, $value) {
        switch ($tag) {
            case 'NAME':
                $record['data']['name'] = $this->parse_name($value);
                break;
            case 'SEX':
                $record['data']['gender'] = $value;
                break;
            case 'BIRT':
                $record['data']['birth'] = array();
                break;
            case 'DEAT':
                $record['data']['death'] = array();
                break;
            case 'MARR':
                $record['data']['marriage'] = array();
                break;
            case 'DATE':
                $this->add_date_to_record($record, $value);
                break;
            case 'PLAC':
                $this->add_place_to_record($record, $value);
                break;
            case 'HUSB':
                $record['data']['husband_id'] = $value;
                break;
            case 'WIFE':
                $record['data']['wife_id'] = $value;
                break;
            case 'CHIL':
                if (!isset($record['data']['children'])) {
                    $record['data']['children'] = array();
                }
                $record['data']['children'][] = $value;
                break;
            case 'FAMC':
                $record['data']['child_family'] = $value;
                break;
            case 'FAMS':
                if (!isset($record['data']['spouse_families'])) {
                    $record['data']['spouse_families'] = array();
                }
                $record['data']['spouse_families'][] = $value;
                break;
            case 'NOTE':
                if (!isset($record['data']['notes'])) {
                    $record['data']['notes'] = '';
                }
                $record['data']['notes'] .= $value . "\n";
                break;
        }
    }
    
    /**
     * Parse name field
     *
     * @param string $name_value Name value from GEDCOM
     * @return array Parsed name parts
     */
    private function parse_name($name_value) {
        // Remove slashes around surname
        $name_value = str_replace('/', '', $name_value);
        $parts = explode(' ', trim($name_value));
        
        $name = array(
            'first_name' => '',
            'last_name' => ''
        );
        
        if (count($parts) > 0) {
            $name['first_name'] = $parts[0];
        }
        
        if (count($parts) > 1) {
            $name['last_name'] = implode(' ', array_slice($parts, 1));
        }
        
        return $name;
    }
    
    /**
     * Add date to record
     *
     * @param array &$record Record reference
     * @param string $date_value Date value
     */
    private function add_date_to_record(&$record, $date_value) {
        $context = $this->get_current_context($record);
        
        if ($context === 'birth') {
            $record['data']['birth']['date'] = $date_value;
        } elseif ($context === 'death') {
            $record['data']['death']['date'] = $date_value;
        } elseif ($context === 'marriage') {
            $record['data']['marriage']['date'] = $date_value;
        }
    }
    
    /**
     * Add place to record
     *
     * @param array &$record Record reference
     * @param string $place_value Place value
     */
    private function add_place_to_record(&$record, $place_value) {
        $context = $this->get_current_context($record);
        
        if ($context === 'birth') {
            $record['data']['birth']['place'] = $place_value;
        } elseif ($context === 'death') {
            $record['data']['death']['place'] = $place_value;
        } elseif ($context === 'marriage') {
            $record['data']['marriage']['place'] = $place_value;
        }
    }
    
    /**
     * Get current context for data entry
     *
     * @param array $record Current record
     * @return string Context
     */
    private function get_current_context($record) {
        // Simple context detection - in real implementation would be more sophisticated
        if (isset($record['data']['birth']) && !isset($record['data']['birth']['date'])) {
            return 'birth';
        }
        if (isset($record['data']['death']) && !isset($record['data']['death']['date'])) {
            return 'death';
        }
        if (isset($record['data']['marriage']) && !isset($record['data']['marriage']['date'])) {
            return 'marriage';
        }
        
        return 'general';
    }
    
    /**
     * Save parsed record
     *
     * @param array $record Record to save
     */
    private function save_record($record) {
        if ($record['type'] === 'INDI') {
            $this->individuals[$record['gedcom_id']] = $record;
        } elseif ($record['type'] === 'FAM') {
            $this->families[$record['gedcom_id']] = $record;
        }
    }
    
    /**
     * Import parsed data to database
     *
     * @return array Import results
     */
    public function import_to_database() {
        global $wpdb;
        
        $results = array(
            'individuals_imported' => 0,
            'families_imported' => 0,
            'errors' => array()
        );
        
        // Import individuals first
        foreach ($this->individuals as $gedcom_id => $individual_data) {
            $individual = new Heritage_Press_Individual();
            
            // Set basic data
            $individual->gedcom_id = $gedcom_id;
            
            if (isset($individual_data['data']['name'])) {
                $individual->first_name = $individual_data['data']['name']['first_name'];
                $individual->last_name = $individual_data['data']['name']['last_name'];
            }
            
            if (isset($individual_data['data']['gender'])) {
                $individual->gender = $individual_data['data']['gender'];
            }
            
            if (isset($individual_data['data']['birth']['date'])) {
                $individual->birth_date = $individual_data['data']['birth']['date'];
            }
            
            if (isset($individual_data['data']['birth']['place'])) {
                $individual->birth_place = $individual_data['data']['birth']['place'];
            }
            
            if (isset($individual_data['data']['death']['date'])) {
                $individual->death_date = $individual_data['data']['death']['date'];
            }
            
            if (isset($individual_data['data']['death']['place'])) {
                $individual->death_place = $individual_data['data']['death']['place'];
            }
            
            if (isset($individual_data['data']['notes'])) {
                $individual->notes = trim($individual_data['data']['notes']);
            }
            
            if ($individual->save()) {
                $results['individuals_imported']++;
            } else {
                $results['errors'][] = 'Failed to save individual: ' . $gedcom_id;
            }
        }
        
        // Import families
        foreach ($this->families as $gedcom_id => $family_data) {
            $family = new Heritage_Press_Family();
            
            $family->gedcom_id = $gedcom_id;
            
            // Find husband and wife by GEDCOM ID
            if (isset($family_data['data']['husband_id'])) {
                $husband = Heritage_Press_Individual::get_by_gedcom_id($family_data['data']['husband_id']);
                if ($husband) {
                    $family->husband_id = $husband->id;
                }
            }
            
            if (isset($family_data['data']['wife_id'])) {
                $wife = Heritage_Press_Individual::get_by_gedcom_id($family_data['data']['wife_id']);
                if ($wife) {
                    $family->wife_id = $wife->id;
                }
            }
            
            if (isset($family_data['data']['marriage']['date'])) {
                $family->marriage_date = $family_data['data']['marriage']['date'];
            }
            
            if (isset($family_data['data']['marriage']['place'])) {
                $family->marriage_place = $family_data['data']['marriage']['place'];
            }
            
            if (isset($family_data['data']['notes'])) {
                $family->notes = trim($family_data['data']['notes']);
            }
            
            if ($family->save()) {
                $results['families_imported']++;
                
                // Add children relationships
                if (isset($family_data['data']['children'])) {
                    foreach ($family_data['data']['children'] as $child_gedcom_id) {
                        $child = Heritage_Press_Individual::get_by_gedcom_id($child_gedcom_id);
                        if ($child) {
                            $family->add_child($child->id);
                        }
                    }
                }
            } else {
                $results['errors'][] = 'Failed to save family: ' . $gedcom_id;
            }
        }
        
        return $results;
    }
    
    /**
     * Get parsed individuals
     *
     * @return array Individuals
     */
    public function get_individuals() {
        return $this->individuals;
    }
    
    /**
     * Get parsed families
     *
     * @return array Families
     */
    public function get_families() {
        return $this->families;
    }
    
    /**
     * Get parse errors
     *
     * @return array Errors
     */
    public function get_errors() {
        return $this->errors;
    }
}
