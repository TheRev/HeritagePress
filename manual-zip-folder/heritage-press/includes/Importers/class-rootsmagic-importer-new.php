<?php
/**
 * RootsMagic Importer
 *
 * Handles importing genealogy data from RootsMagic database files (.rmgc).
 * Supports RootsMagic versions 9 and 10.
 *
 * @package HeritagePress
 * @subpackage Importers
 */

namespace HeritagePress\Importers;

use PDO;
use PDOException;
use Exception;
use HeritagePress\Models\Individual;
use HeritagePress\Models\Family;
use HeritagePress\Models\Event;
use HeritagePress\Models\Place;
use HeritagePress\Models\Source;
use HeritagePress\Models\Citation;

class RootsMagic_Importer implements Genealogy_Importer {/**
     * RootsMagic database handler
     *
     * @var RootsMagic_Database
     */
    private $database = null;

    /**
     * @inheritDoc
     */
    public function can_import($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        try {
            $this->database = new RootsMagic_Database($file_path);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function get_format_name() {
        return 'RootsMagic';
    }

    /**
     * @inheritDoc
     */
    public function get_supported_extensions() {
        return ['rmtree'];
    }

    /**
     * @inheritDoc
     */
    public function import($file_path, $options = []) {
        if (!$this->can_import($file_path)) {
            throw new \Exception('Invalid RootsMagic file format');
        }

        try {
            // Start transaction
            global $wpdb;
            $wpdb->query('START TRANSACTION');

            // Connect to RootsMagic database
            $this->db = $this->connect_to_database($file_path);
            
            // Import data based on version
            if ($this->version >= 10) {
                $data = $this->import_rm10_data();
            } elseif ($this->version >= 9) {
                $data = $this->import_rm9_data();
            } else {
                throw new \Exception('Unsupported RootsMagic version: ' . $this->version);
            }

            // Commit transaction
            $wpdb->query('COMMIT');
            return [
                'success' => true,
                'imported_individuals' => count($data['individuals']),
                'imported_families' => count($data['families']),
                'imported_events' => count($data['events']),
                'imported_places' => count($data['places']),
                'imported_sources' => count($data['sources'])
            ];

        } catch (\Exception $e) {
            if (isset($wpdb)) {
                $wpdb->query('ROLLBACK');
            }
            throw $e;
        } finally {
            $this->db = null; // Close database connection
        }
    }

    /**
     * Import data from RootsMagic 10
     */
    private function import_rm10_data() {
        $data = [
            'individuals' => [],
            'families' => [],
            'events' => [],
            'places' => [],
            'sources' => []
        ];

        // Import people
        $stmt = $this->db->query("SELECT * FROM PersonTable");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data['individuals'][] = $this->import_individual($this->map_rm10_person($row));
        }

        // Import families
        $stmt = $this->db->query("SELECT * FROM FamilyTable");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data['families'][] = $this->import_family($this->map_rm10_family($row));
        }

        // Import events
        $stmt = $this->db->query("SELECT * FROM EventTable");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data['events'][] = $this->import_event($this->map_rm10_event($row));
        }

        // Import places
        $stmt = $this->db->query("SELECT * FROM PlaceTable");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data['places'][] = $this->import_place($this->map_rm10_place($row));
        }

        // Import sources
        $stmt = $this->db->query("SELECT * FROM SourceTable");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data['sources'][] = $this->import_source($this->map_rm10_source($row));
        }

        return $data;
    }

    /**
     * Import data from RootsMagic 9
     */
    private function import_rm9_data() {
        $data = [
            'individuals' => [],
            'families' => [],
            'events' => [],
            'places' => [],
            'sources' => []
        ];

        // Import people
        $stmt = $this->db->query("SELECT * FROM People");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data['individuals'][] = $this->import_individual($this->map_rm9_person($row));
        }

        // Import families
        $stmt = $this->db->query("SELECT * FROM Families");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data['families'][] = $this->import_family($this->map_rm9_family($row));
        }

        // Import events
        $stmt = $this->db->query("SELECT * FROM Events");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data['events'][] = $this->import_event($this->map_rm9_event($row));
        }

        // Import places
        $stmt = $this->db->query("SELECT * FROM Places");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data['places'][] = $this->import_place($this->map_rm9_place($row));
        }

        // Import sources
        $stmt = $this->db->query("SELECT * FROM Sources");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data['sources'][] = $this->import_source($this->map_rm9_source($row));
        }

        return $data;
    }

    /**
     * Import individual record
     */
    private function import_individual($data) {
        return Individual::create($data);
    }

    /**
     * Import family record
     */
    private function import_family($data) {
        return Family::create($data);
    }

    /**
     * Import event record
     */
    private function import_event($data) {
        return Event::create($data);
    }

    /**
     * Import place record
     */
    private function import_place($data) {
        return Place::create($data);
    }

    /**
     * Import source record
     */
    private function import_source($data) {
        return Source::create($data);
    }

    /**
     * Map person data from RootsMagic 10 format
     */
    private function map_rm10_person($data) {
        return [
            'given_names' => $data['GivenName'] ?? '',
            'surname' => $data['Surname'] ?? '',
            'gender' => $this->map_gender($data['Sex'] ?? ''),
            'birth_date' => $data['BirthDate'] ?? '',
            'death_date' => $data['DeathDate'] ?? '',
            'note' => $data['Note'] ?? '',
            'uuid' => $data['ID'] ?? null,
            'private' => ($data['Private'] ?? 0) == 1
        ];
    }

    /**
     * Map person data from RootsMagic 9 format
     */
    private function map_rm9_person($data) {
        return [
            'given_names' => $data['GivenName'] ?? '',
            'surname' => $data['SurName'] ?? '',
            'gender' => $this->map_gender($data['Sex'] ?? ''),
            'birth_date' => $data['BirthDate'] ?? '',
            'death_date' => $data['DeathDate'] ?? '',
            'note' => $data['Note'] ?? '',
            'uuid' => $data['ID'] ?? null,
            'private' => ($data['Private'] ?? 0) == 1
        ];
    }

    /**
     * Map family data from RootsMagic 10 format
     */
    private function map_rm10_family($data) {
        return [
            'husband_id' => $data['FatherID'] ?? null,
            'wife_id' => $data['MotherID'] ?? null,
            'marriage_date' => $data['MarriageDate'] ?? '',
            'marriage_place_id' => $data['MarriagePlaceID'] ?? null,
            'note' => $data['Note'] ?? '',
            'uuid' => $data['ID'] ?? null,
            'private' => ($data['Private'] ?? 0) == 1
        ];
    }

    /**
     * Map family data from RootsMagic 9 format
     */
    private function map_rm9_family($data) {
        return [
            'husband_id' => $data['FatherID'] ?? null,
            'wife_id' => $data['MotherID'] ?? null,
            'marriage_date' => $data['MarriageDate'] ?? '',
            'marriage_place_id' => $data['MarriagePlaceID'] ?? null,
            'note' => $data['Note'] ?? '',
            'uuid' => $data['ID'] ?? null,
            'private' => ($data['Private'] ?? 0) == 1
        ];
    }

    /**
     * Map event data from RootsMagic 10 format
     */
    private function map_rm10_event($data) {
        return [
            'type' => $data['EventType'] ?? '',
            'date' => $data['EventDate'] ?? '',
            'place_id' => $data['PlaceID'] ?? null,
            'description' => $data['Description'] ?? '',
            'uuid' => $data['ID'] ?? null,
            'private' => ($data['Private'] ?? 0) == 1
        ];
    }

    /**
     * Map event data from RootsMagic 9 format
     */
    private function map_rm9_event($data) {
        return [
            'type' => $data['EventType'] ?? '',
            'date' => $data['EventDate'] ?? '',
            'place_id' => $data['PlaceID'] ?? null,
            'description' => $data['Description'] ?? '',
            'uuid' => $data['ID'] ?? null,
            'private' => ($data['Private'] ?? 0) == 1
        ];
    }

    /**
     * Map place data from RootsMagic 10 format
     */
    private function map_rm10_place($data) {
        return [
            'name' => $data['PlaceName'] ?? '',
            'latitude' => $data['Latitude'] ?? null,
            'longitude' => $data['Longitude'] ?? null,
            'note' => $data['Note'] ?? '',
            'uuid' => $data['ID'] ?? null,
            'private' => ($data['Private'] ?? 0) == 1
        ];
    }

    /**
     * Map place data from RootsMagic 9 format
     */
    private function map_rm9_place($data) {
        return [
            'name' => $data['PlaceName'] ?? '',
            'latitude' => $data['Latitude'] ?? null,
            'longitude' => $data['Longitude'] ?? null,
            'note' => $data['Note'] ?? '',
            'uuid' => $data['ID'] ?? null,
            'private' => ($data['Private'] ?? 0) == 1
        ];
    }

    /**
     * Map source data from RootsMagic 10 format
     */
    private function map_rm10_source($data) {
        return [
            'title' => $data['Title'] ?? '',
            'author' => $data['Author'] ?? '',
            'year' => $data['Year'] ?? null,
            'note' => $data['Note'] ?? '',
            'uuid' => $data['ID'] ?? null,
            'private' => ($data['Private'] ?? 0) == 1
        ];
    }

    /**
     * Map source data from RootsMagic 9 format
     */
    private function map_rm9_source($data) {
        return [
            'title' => $data['Title'] ?? '',
            'author' => $data['Author'] ?? '',
            'year' => $data['Year'] ?? null,
            'note' => $data['Note'] ?? '',
            'uuid' => $data['ID'] ?? null,
            'private' => ($data['Private'] ?? 0) == 1
        ];
    }

    /**
     * Get database handler
     */
    public function get_database() {
        return $this->database;
    }

    /**
     * Get all individuals from the database
     */
    public function get_individuals() {
        if (!$this->database) {
            throw new Exception('No database connection');
        }

        if ($this->database->get_version() === 10) {
            return $this->get_individuals_rm10();
        } else {
            return $this->get_individuals_rm9();
        }
    }

    /**
     * Get individuals from RootsMagic 10
     */
    private function get_individuals_rm10() {
        $query = "SELECT PersonId as uuid, GivenName as given_name, Surname as surname, " .
                "Gender as sex, IsPrivate as is_private, BirthDate as birth_date, " .
                "DeathDate as death_date FROM Person";
        return $this->database->query($query);
    }

    /**
     * Get individuals from RootsMagic 9
     */
    private function get_individuals_rm9() {
        $query = "SELECT UniqueID as uuid, GivenName as given_name, Surname as surname, " .
                "Sex as sex, Private as is_private FROM NameTable";
        return $this->database->query($query);
    }

    /**
     * Get all families from the database
     */
    public function get_families() {
        if (!$this->database) {
            throw new Exception('No database connection');
        }

        if ($this->database->get_version() === 10) {
            return $this->get_families_rm10();
        } else {
            return $this->get_families_rm9();
        }
    }

    /**
     * Get families from RootsMagic 10
     */
    private function get_families_rm10() {
        $query = "SELECT FamilyId as uuid, FatherId as husband_id, MotherId as wife_id, " .
                "MarriageDate as marriage_date, MarriagePlace as marriage_place FROM Family";
        return $this->database->query($query);
    }

    /**
     * Get families from RootsMagic 9
     */
    private function get_families_rm9() {
        $query = "SELECT FamilyID as uuid, HusbandID as husband_id, WifeID as wife_id ".
                "FROM FamilyTable";
        return $this->database->query($query);
    }
}
