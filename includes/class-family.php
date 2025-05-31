<?php
/**
 * Family class for Heritage Press
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Heritage Press Family Class
 */
class Heritage_Press_Family {
    
    /**
     * Family ID
     */
    public $id;
    
    /**
     * GEDCOM ID
     */
    public $gedcom_id;
    
    /**
     * Husband ID
     */
    public $husband_id;
    
    /**
     * Wife ID
     */
    public $wife_id;
    
    /**
     * Marriage date
     */
    public $marriage_date;
    
    /**
     * Marriage place
     */
    public $marriage_place;
    
    /**
     * Notes
     */
    public $notes;
    
    /**
     * Constructor
     */
    public function __construct($data = array()) {
        if (!empty($data)) {
            $this->load_data($data);
        }
    }
    
    /**
     * Load data into object
     */
    private function load_data($data) {
        $properties = array(
            'id', 'gedcom_id', 'husband_id', 'wife_id',
            'marriage_date', 'marriage_place', 'notes'
        );
        
        foreach ($properties as $property) {
            if (isset($data[$property])) {
                $this->$property = $data[$property];
            }
        }
    }
    
    /**
     * Save family to database
     */
    public function save() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'heritage_families';
        
        $data = array(
            'gedcom_id' => $this->gedcom_id,
            'husband_id' => $this->husband_id,
            'wife_id' => $this->wife_id,
            'marriage_date' => $this->marriage_date,
            'marriage_place' => $this->marriage_place,
            'notes' => $this->notes
        );
        
        $formats = array('%s', '%d', '%d', '%s', '%s', '%s');
        
        if ($this->id) {
            // Update existing record
            $wpdb->update($table_name, $data, array('id' => $this->id), $formats, array('%d'));
        } else {
            // Insert new record
            $wpdb->insert($table_name, $data, $formats);
            $this->id = $wpdb->insert_id;
        }
        
        return $this->id;
    }
    
    /**
     * Delete family from database
     */
    public function delete() {
        global $wpdb;
        
        if (!$this->id) {
            return false;
        }
        
        // Delete relationships first
        $relationships_table = $wpdb->prefix . 'heritage_relationships';
        $wpdb->delete($relationships_table, array('family_id' => $this->id), array('%d'));
        
        // Delete family record
        $table_name = $wpdb->prefix . 'heritage_families';
        return $wpdb->delete($table_name, array('id' => $this->id), array('%d'));
    }
    
    /**
     * Get family by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'heritage_families';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
        
        if ($result) {
            return new self($result);
        }
        
        return null;
    }
    
    /**
     * Get family by GEDCOM ID
     */
    public static function get_by_gedcom_id($gedcom_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'heritage_families';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE gedcom_id = %s", $gedcom_id), ARRAY_A);
        
        if ($result) {
            return new self($result);
        }
        
        return null;
    }
    
    /**
     * Get all families
     */
    public static function get_all($limit = 50, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'heritage_families';
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY id LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A);
        
        $families = array();
        foreach ($results as $result) {
            $families[] = new self($result);
        }
        
        return $families;
    }
    
    /**
     * Get husband
     */
    public function get_husband() {
        if (empty($this->husband_id)) {
            return null;
        }
        
        return Heritage_Press_Individual::get_by_id($this->husband_id);
    }
    
    /**
     * Get wife
     */
    public function get_wife() {
        if (empty($this->wife_id)) {
            return null;
        }
        
        return Heritage_Press_Individual::get_by_id($this->wife_id);
    }
    
    /**
     * Get children
     */
    public function get_children() {
        global $wpdb;
        
        $relationships_table = $wpdb->prefix . 'heritage_relationships';
        $individuals_table = $wpdb->prefix . 'heritage_individuals';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT i.* FROM $individuals_table i 
             INNER JOIN $relationships_table r ON i.id = r.individual_id 
             WHERE r.family_id = %d AND r.relationship_type = 'child'
             ORDER BY i.birth_date",
            $this->id
        ), ARRAY_A);
        
        $children = array();
        foreach ($results as $result) {
            $children[] = new Heritage_Press_Individual($result);
        }
        
        return $children;
    }
    
    /**
     * Add child to family
     */
    public function add_child($individual_id) {
        global $wpdb;
        
        $relationships_table = $wpdb->prefix . 'heritage_relationships';
        
        return $wpdb->insert(
            $relationships_table,
            array(
                'family_id' => $this->id,
                'individual_id' => $individual_id,
                'relationship_type' => 'child'
            ),
            array('%d', '%d', '%s')
        );
    }
    
    /**
     * Remove child from family
     */
    public function remove_child($individual_id) {
        global $wpdb;
        
        $relationships_table = $wpdb->prefix . 'heritage_relationships';
        
        return $wpdb->delete(
            $relationships_table,
            array(
                'family_id' => $this->id,
                'individual_id' => $individual_id,
                'relationship_type' => 'child'
            ),
            array('%d', '%d', '%s')
        );
    }
    
    /**
     * Get family display name
     */
    public function get_display_name() {
        $husband = $this->get_husband();
        $wife = $this->get_wife();
        
        $names = array();
        
        if ($husband) {
            $names[] = $husband->get_full_name();
        }
        
        if ($wife) {
            $names[] = $wife->get_full_name();
        }
        
        if (empty($names)) {
            return __('Unknown Family', 'heritage-press');
        }
        
        return implode(' & ', $names);
    }
    
    /**
     * Get marriage year
     */
    public function get_marriage_year() {
        if (empty($this->marriage_date)) {
            return '';
        }
        
        // Extract year from various date formats
        if (preg_match('/(\d{4})/', $this->marriage_date, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
}
