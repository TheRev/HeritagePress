<?php
namespace HeritagePress\Database;

use wpdb;

/**
 * Database operations trait used by Admin and PageRenderer classes
 */
trait DatabaseOperations
{
    /** @var wpdb WordPress database object */
    protected $wpdb;

    public function get_recent_individuals($limit = 50)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT i.*, n.given_names, n.surname 
             FROM {$this->wpdb->prefix}hp_individuals i
             LEFT JOIN {$this->wpdb->prefix}hp_names n ON i.id = n.individual_id AND n.is_primary = 1
             ORDER BY i.updated_at DESC
             LIMIT %d",
            $limit
        ));
    }

    public function get_individual($id)
    {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT i.*, n.given_names, n.surname 
             FROM {$this->wpdb->prefix}hp_individuals i
             LEFT JOIN {$this->wpdb->prefix}hp_names n ON i.id = n.individual_id AND n.is_primary = 1 
             WHERE i.id = %d",
            $id
        ));
    }

    public function get_individual_names($individual_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_names WHERE individual_id = %d ORDER BY is_primary DESC",
            $individual_id
        ));
    }

    public function get_individuals()
    {
        return $this->wpdb->get_results(
            "SELECT i.*, n.given_names, n.surname 
             FROM {$this->wpdb->prefix}hp_individuals i
             LEFT JOIN {$this->wpdb->prefix}hp_names n ON i.id = n.individual_id AND n.is_primary = 1
             ORDER BY n.surname, n.given_names"
        );
    }

    public function get_individual_events($individual_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT e.*, t.name as type_name, p.name as place_name
             FROM {$this->wpdb->prefix}hp_events e
             LEFT JOIN {$this->wpdb->prefix}hp_event_types t ON e.type_id = t.id
             LEFT JOIN {$this->wpdb->prefix}hp_places p ON e.place_id = p.id
             WHERE e.individual_id = %d
             ORDER BY e.date",
            $individual_id
        ));
    }

    public function get_event_types()
    {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}hp_event_types ORDER BY name");
    }

    public function get_families()
    {
        return $this->wpdb->get_results(
            "SELECT f.*,
                    h.id as husband_id, h_name.given_names as husband_given_names, h_name.surname as husband_surname,
                    w.id as wife_id, w_name.given_names as wife_given_names, w_name.surname as wife_surname
             FROM {$this->wpdb->prefix}hp_families f
             LEFT JOIN {$this->wpdb->prefix}hp_individuals h ON f.husband_id = h.id
             LEFT JOIN {$this->wpdb->prefix}hp_names h_name ON h.id = h_name.individual_id AND h_name.is_primary = 1
             LEFT JOIN {$this->wpdb->prefix}hp_individuals w ON f.wife_id = w.id
             LEFT JOIN {$this->wpdb->prefix}hp_names w_name ON w.id = w_name.individual_id AND w_name.is_primary = 1
             ORDER BY f.updated_at DESC"
        );
    }

    public function get_family($id)
    {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT f.*,
                    h.id as husband_id, h_name.given_names as husband_given_names, h_name.surname as husband_surname,
                    w.id as wife_id, w_name.given_names as wife_given_names, w_name.surname as wife_surname
             FROM {$this->wpdb->prefix}hp_families f
             LEFT JOIN {$this->wpdb->prefix}hp_individuals h ON f.husband_id = h.id
             LEFT JOIN {$this->wpdb->prefix}hp_names h_name ON h.id = h_name.individual_id AND h_name.is_primary = 1
             LEFT JOIN {$this->wpdb->prefix}hp_individuals w ON f.wife_id = w.id
             LEFT JOIN {$this->wpdb->prefix}hp_names w_name ON w.id = w_name.individual_id AND w_name.is_primary = 1
             WHERE f.id = %d",
            $id
        ));
    }

    public function get_family_members($family_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT r.*, i.id, n.given_names, n.surname
             FROM {$this->wpdb->prefix}hp_family_links r
             JOIN {$this->wpdb->prefix}hp_individuals i ON r.individual_id = i.id
             LEFT JOIN {$this->wpdb->prefix}hp_names n ON i.id = n.individual_id AND n.is_primary = 1
             WHERE r.family_id = %d
             ORDER BY r.relationship_type, n.surname, n.given_names",
            $family_id
        ));
    }

    public function get_family_events($family_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT e.*, t.name as type_name, p.name as place_name
             FROM {$this->wpdb->prefix}hp_events e
             LEFT JOIN {$this->wpdb->prefix}hp_event_types t ON e.type_id = t.id
             LEFT JOIN {$this->wpdb->prefix}hp_places p ON e.place_id = p.id
             WHERE e.family_id = %d
             ORDER BY e.date",
            $family_id
        ));
    }

    public function get_source_types()
    {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}hp_source_types ORDER BY name");
    }

    public function get_sources()
    {
        return $this->wpdb->get_results(
            "SELECT s.*, r.name as repository_name 
             FROM {$this->wpdb->prefix}hp_sources s
             LEFT JOIN {$this->wpdb->prefix}hp_repositories r ON s.repository_id = r.id
             ORDER BY s.updated_at DESC"
        );
    }

    public function get_source($id)
    {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT s.*, r.name as repository_name
             FROM {$this->wpdb->prefix}hp_sources s
             LEFT JOIN {$this->wpdb->prefix}hp_repositories r ON s.repository_id = r.id
             WHERE s.id = %d",
            $id
        ));
    }

    public function get_source_citations($source_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT c.*, 
                    i.id as individual_id, i_name.given_names as individual_given_names, i_name.surname as individual_surname,
                    f.id as family_id, h_name.surname as family_surname
             FROM {$this->wpdb->prefix}hp_citations c
             LEFT JOIN {$this->wpdb->prefix}hp_individuals i ON c.individual_id = i.id
             LEFT JOIN {$this->wpdb->prefix}hp_names i_name ON i.id = i_name.individual_id AND i_name.is_primary = 1
             LEFT JOIN {$this->wpdb->prefix}hp_families f ON c.family_id = f.id
             LEFT JOIN {$this->wpdb->prefix}hp_individuals h ON f.husband_id = h.id
             LEFT JOIN {$this->wpdb->prefix}hp_names h_name ON h.id = h_name.individual_id AND h_name.is_primary = 1
             WHERE c.source_id = %d
             ORDER BY c.page_number, c.created_at",
            $source_id
        ));
    }

    public function get_media_items()
    {
        return $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}hp_media_objects ORDER BY updated_at DESC");
    }

    public function get_media($id)
    {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_media_objects WHERE id = %d",
            $id
        ));
    }

    public function get_media_references($media_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT l.*, 
                    i.id as individual_id, i_name.given_names as individual_given_names, i_name.surname as individual_surname,
                    f.id as family_id, h_name.surname as family_surname,
                    s.id as source_id, s.title as source_title
             FROM {$this->wpdb->prefix}hp_media_links l
             LEFT JOIN {$this->wpdb->prefix}hp_individuals i ON l.individual_id = i.id
             LEFT JOIN {$this->wpdb->prefix}hp_names i_name ON i.id = i_name.individual_id AND i_name.is_primary = 1
             LEFT JOIN {$this->wpdb->prefix}hp_families f ON l.family_id = f.id
             LEFT JOIN {$this->wpdb->prefix}hp_individuals h ON f.husband_id = h.id
             LEFT JOIN {$this->wpdb->prefix}hp_names h_name ON h.id = h_name.individual_id AND h_name.is_primary = 1
             LEFT JOIN {$this->wpdb->prefix}hp_sources s ON l.source_id = s.id
             WHERE l.media_id = %d
             ORDER BY l.created_at",
            $media_id
        ));
    }

    public function get_dna_test($id)
    {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT t.*, 
                    i.id as individual_id, i_name.given_names as individual_given_names, i_name.surname as individual_surname
             FROM {$this->wpdb->prefix}hp_dna_tests t
             JOIN {$this->wpdb->prefix}hp_individuals i ON t.individual_id = i.id
             LEFT JOIN {$this->wpdb->prefix}hp_names i_name ON i.id = i_name.individual_id AND i_name.is_primary = 1
             WHERE t.id = %d",
            $id
        ));
    }

    public function get_dna_matches($test_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_dna_matches WHERE test_id = %d ORDER BY match_date DESC",
            $test_id
        ));
    }

    public function get_dna_tests()
    {
        return $this->wpdb->get_results(
            "SELECT t.*, 
                    i.id as individual_id, i_name.given_names as individual_given_names, i_name.surname as individual_surname
             FROM {$this->wpdb->prefix}hp_dna_tests t
             JOIN {$this->wpdb->prefix}hp_individuals i ON t.individual_id = i.id
             LEFT JOIN {$this->wpdb->prefix}hp_names i_name ON i.id = i_name.individual_id AND i_name.is_primary = 1
             ORDER BY t.test_date DESC"
        );
    }

    public function get_recent_families($limit = 50)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT f.*,
                    h.id as husband_id, h_name.given_names as husband_given_names, h_name.surname as husband_surname,
                    w.id as wife_id, w_name.given_names as wife_given_names, w_name.surname as wife_surname
             FROM {$this->wpdb->prefix}hp_families f
             LEFT JOIN {$this->wpdb->prefix}hp_individuals h ON f.husband_id = h.id
             LEFT JOIN {$this->wpdb->prefix}hp_names h_name ON h.id = h_name.individual_id AND h_name.is_primary = 1
             LEFT JOIN {$this->wpdb->prefix}hp_individuals w ON f.wife_id = w.id
             LEFT JOIN {$this->wpdb->prefix}hp_names w_name ON w.id = w_name.individual_id AND w_name.is_primary = 1
             ORDER BY f.updated_at DESC
             LIMIT %d",
            $limit
        ));
    }

    public function get_recent_sources($limit = 50)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT s.*, r.name as repository_name
             FROM {$this->wpdb->prefix}hp_sources s
             LEFT JOIN {$this->wpdb->prefix}hp_repositories r ON s.repository_id = r.id
             ORDER BY s.updated_at DESC
             LIMIT %d",
            $limit
        ));
    }

    public function get_recent_media($limit = 50)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}hp_media_objects
             ORDER BY updated_at DESC
             LIMIT %d",
            $limit
        ));
    }
}
