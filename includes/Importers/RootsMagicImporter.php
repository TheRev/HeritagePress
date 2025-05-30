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

class RootsMagicImporter implements Genealogy_Importer {
    /**
     * Database file path
     *
     * @var string
     */
    private $file_path = null;

    /**
     * RootsMagic version (9 or 10)
     *
     * @var int
     */
    private $version = null;

    /**
     * Error messages
     *
     * @var array
     */
    private $errors = [];

    /**
     * @inheritDoc
     */
    public function can_import($file_path) {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            $this->errors[] = "File does not exist or is not readable";
            return false;
        }

        // Simple check for SQLite format
        $header = file_get_contents($file_path, false, null, 0, 16);
        if (substr($header, 0, 16) !== 'SQLite format 3') {
            $this->errors[] = "File is not a valid SQLite database";
            return false;
        }
        
        $this->file_path = $file_path;
        
        // We won't try to connect until required during import
        return true;
    }
    /**
     * Get the name of this import format
     *
     * @return string
     */
    public function get_format_name() {
        return 'RootsMagic';
    }

    /**
     * Get supported file extensions
     *
     * @return array
     */
    public function get_supported_extensions() {
        return ['rmtree'];
    }
    /**
     * Validate the file before importing
     *
     * @param string $file_path
     * @return array Validation results with any errors or warnings
     */
    public function validate($file_path) {
        $result = [
            'valid' => $this->can_import($file_path),
            'errors' => $this->errors,
            'warnings' => [],
        ];
        
        // Additional checks if the file is initially valid
        if ($result['valid'] && extension_loaded('pdo_sqlite')) {
            try {
                // Check database version and structure
                $pdo = new PDO("sqlite:{$file_path}");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Detect version
                $this->detect_version($pdo);
                
                // Check required tables based on version
                if ($this->version == 10) {
                    $required_tables = ['Person', 'Family', 'Event'];
                } else {
                    $required_tables = ['NameTable', 'FamilyTable', 'EventTable'];
                }
                
                foreach ($required_tables as $table) {
                    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
                    if (!$stmt->fetch()) {
                        $result['valid'] = false;
                        $result['errors'][] = "Required table '$table' not found";
                    }
                }
                
                // Add version to result
                if ($this->version) {
                    $result['version'] = $this->version;
                }
            } catch (PDOException $e) {
                $result['valid'] = false;
                $result['errors'][] = "Database error: " . $e->getMessage();
            }
        } elseif ($result['valid']) {
            $result['warnings'][] = "SQLite PDO extension not available. Full validation skipped.";
        }
        
        return $result;
    }
    /**
     * Import data from the file
     *
     * @param string $file_path
     * @param array $options Import options
     * @return array Import statistics
     */
    public function import($file_path, $options = []) {
        if (!$this->can_import($file_path)) {
            throw new Exception('Cannot import file: ' . $file_path);
        }
        
        if (!extension_loaded('pdo_sqlite')) {
            throw new Exception('SQLite PDO extension is required for importing RootsMagic files');
        }

        $stats = [
            'individuals' => 0,
            'families' => 0,
            'events' => 0,
            'places' => 0,
            'sources' => 0,
            'citations' => 0,
            'media' => 0, // Added for media
            'notes' => 0    // Added for notes
        ];

        try {
            // Connect to database and detect version
            $db = new PDO("sqlite:{$file_path}");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $version = $this->detect_version($db);
            
            if (!$version) {
                throw new Exception('Unable to detect RootsMagic version from database');
            }
            
            // Import based on version
            if ($version === 10) {
                return $this->import_rm10_data($db, $options);
            } else {
                return $this->import_rm9_data($db, $options);
            }
        } catch (Exception $e) {
            throw new Exception('Import failed: ' . $e->getMessage());
        }
    }
    /**
     * Get individuals from the database
     *
     * @param PDO $db Database connection
     * @return array Array of individuals
     */
    public function get_individuals($db = null) {
        if (!$db && !extension_loaded('pdo_sqlite')) {
            throw new Exception('SQLite PDO extension is required');
        }
        
        if (!$db) {
            $db = new PDO("sqlite:{$this->file_path}");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        $version = $this->detect_version($db);
        
        if ($version === 10) {
            return $this->get_individuals_rm10($db);
        } else {
            return $this->get_individuals_rm9($db);
        }
    }
    
    /**
     * Get individuals from RootsMagic 10
     *
     * @param PDO $db Database connection
     * @return array Array of individuals
     */
    private function get_individuals_rm10($db) {
        $query = "SELECT 
            PersonId as uuid,
            GivenName as given_name,
            Surname as surname,
            Gender as sex,
            IsPrivate as is_private,
            BirthDate as birth_date,
            DeathDate as death_date
        FROM Person";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        
        return $result;
    }
    
    /**
     * Get individuals from RootsMagic 9
     *
     * @param PDO $db Database connection
     * @return array Array of individuals
     */
    private function get_individuals_rm9($db) {
        $query = "SELECT 
            UniqueID as uuid,
            GivenName as given_name,
            Surname as surname,
            Sex as sex,
            Private as is_private
        FROM NameTable";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get birth and death dates
            $birth_query = "SELECT EventDate as birth_date FROM EventTable 
                        WHERE OwnerID = :id AND EventType = 'BIRT' LIMIT 1";
            $birth_stmt = $db->prepare($birth_query);
            $birth_stmt->execute(['id' => $row['uuid']]);
            $birth = $birth_stmt->fetch(PDO::FETCH_ASSOC);
            
            $death_query = "SELECT EventDate as death_date FROM EventTable 
                        WHERE OwnerID = :id AND EventType = 'DEAT' LIMIT 1";
            $death_stmt = $db->prepare($death_query);
            $death_stmt->execute(['id' => $row['uuid']]);
            $death = $death_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Add birth and death dates
            if ($birth) {
                $row['birth_date'] = $birth['birth_date'];
            }
            if ($death) {
                $row['death_date'] = $death['death_date'];
            }
            
            $result[] = $row;
        }
        
        return $result;
    }
    
    /**
     * Get families from the database
     *
     * @param PDO $db Database connection
     * @return array Array of families
     */
    public function get_families($db = null) {
        if (!$db && !extension_loaded('pdo_sqlite')) {
            throw new Exception('SQLite PDO extension is required');
        }
        
        if (!$db) {
            $db = new PDO("sqlite:{$this->file_path}");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        $version = $this->detect_version($db);
        
        if ($version === 10) {
            return $this->get_families_rm10($db);
        } else {
            return $this->get_families_rm9($db);
        }
    }

    /**
     * Get families from RootsMagic 10
     *
     * @param PDO $db Database connection
     * @return array Array of families
     */
    private function get_families_rm10($db) {
        $query = "SELECT 
            FamilyId as uuid,
            FatherId as husband_id,
            MotherId as wife_id,
            MarriageDate as marriage_date,
            MarriagePlace as marriage_place 
        FROM Family";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Get families from RootsMagic 9
     *
     * @param PDO $db Database connection
     * @return array Array of families
     */
    private function get_families_rm9($db) {
        $query = "SELECT 
            FamilyID as uuid,
            HusbandID as husband_id,
            WifeID as wife_id
        FROM FamilyTable";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get marriage date and place
            $marriage_query = "SELECT EventDate as marriage_date, EventPlace as marriage_place 
                          FROM EventTable 
                          WHERE OwnerID = :id AND EventType = 'MARR' LIMIT 1";
            $marriage_stmt = $db->prepare($marriage_query);
            $marriage_stmt->execute(['id' => $row['uuid']]);
            $marriage = $marriage_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($marriage) {
                $row['marriage_date'] = $marriage['marriage_date'];
                $row['marriage_place'] = $marriage['marriage_place'];
            }
            $result[] = $row;
        }
        return $result;
    }
    
    /**
     * Get events from the database
     *
     * @param PDO $db Database connection
     * @return array Array of events
     */
    public function get_events($db = null) {
        if (!$db && !extension_loaded('pdo_sqlite')) {
            throw new Exception('SQLite PDO extension is required');
        }
        
        if (!$db) {
            $db = new PDO("sqlite:{$this->file_path}");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        $version = $this->detect_version($db);
        
        if ($version === 10) {
            return $this->get_events_rm10($db);
        } else {
            return $this->get_events_rm9($db);
        }
    }

    /**
     * Get events from RootsMagic 10
     *
     * @param PDO $db Database connection
     * @return array Array of events
     */
    private function get_events_rm10($db) {
        $query = "SELECT 
            EventId as uuid,
            EventType as type,
            EventDate as date,
            EventPlace as place,
            EventMemo as description,
            PersonId as person_id,
            FamilyId as family_id
        FROM Event";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
    
    /**
     * Get events from RootsMagic 9
     *
     * @param PDO $db Database connection
     * @return array Array of events
     */
    private function get_events_rm9($db) {
        $query = "SELECT 
            EventID as uuid,
            EventType as type,
            EventDate as date,
            EventPlace as place,
            EventNote as description,
            OwnerID as owner_id,
            OwnerType as owner_type
        FROM EventTable";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['owner_type'] == 'I') {
                $row['person_id'] = $row['owner_id'];
            } else if ($row['owner_type'] == 'F') {
                $row['family_id'] = $row['owner_id'];
            }
            unset($row['owner_id'], $row['owner_type']);
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Get places from the database
     *
     * @param PDO $db Database connection
     * @return array Array of places
     */
    public function get_places($db = null) {
        if (!$db && !extension_loaded('pdo_sqlite')) {
            throw new Exception('SQLite PDO extension is required');
        }
        
        if (!$db) {
            $db = new PDO("sqlite:{$this->file_path}");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        $version = $this->detect_version($db);
        
        if ($version === 10) {
            return $this->get_places_rm10($db);
        } else {
            return $this->get_places_rm9($db);
        }
    }

    /**
     * Get places from RootsMagic 10
     *
     * @param PDO $db Database connection
     * @return array Array of places
     */
    private function get_places_rm10($db) {
        $query = "SELECT 
            PlaceId as uuid,
            PlaceName as name,
            Latitude as latitude,
            Longitude as longitude
        FROM Place";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        
        return $result;
    }
    
    /**
     * Get places from RootsMagic 9
     *
     * @param PDO $db Database connection
     * @return array Array of places
     */
    private function get_places_rm9($db) {
        $query = "SELECT 
            PlaceID as uuid,
            PlaceName as name,
            Latitude as latitude,
            Longitude as longitude
        FROM PlaceTable";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        
        return $result;
    }
    
    /**
     * Get sources from the database
     *
     * @param PDO $db Database connection
     * @return array Array of sources
     */
    public function get_sources($db = null) {
        if (!$db && !extension_loaded('pdo_sqlite')) {
            throw new Exception('SQLite PDO extension is required');
        }
        
        if (!$db) {
            $db = new PDO("sqlite:{$this->file_path}");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        $version = $this->detect_version($db);
        
        if ($version === 10) {
            return $this->get_sources_rm10($db);
        } else {
            return $this->get_sources_rm9($db);
        }
    }

    /**
     * Get sources from RootsMagic 10
     *
     * @param PDO $db Database connection
     * @return array Array of sources
     */
    private function get_sources_rm10($db) {
        $query = "SELECT 
            SourceId as uuid,
            SourceName as title,
            SourceRef as reference,
            Comments as comments
        FROM Source";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        
        return $result;
    }
    
    /**
     * Get sources from RootsMagic 9
     *
     * @param PDO $db Database connection
     * @return array Array of sources
     */
    private function get_sources_rm9($db) {
        $query = "SELECT 
            SourceID as uuid,
            Title as title,
            ActualText as text
        FROM SourceTable";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        
        return $result;
    }

    /**
     * Get citations from the database
     *
     * @param PDO $db Database connection
     * @return array Array of citations
     */
    public function get_citations($db = null) {
        if (!$db && !extension_loaded('pdo_sqlite')) {
            throw new Exception('SQLite PDO extension is required');
        }
        if (!$db) {
            $db = new PDO("sqlite:{$this->file_path}");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        $version = $this->detect_version($db);
        if ($version === 10) {
            return $this->get_citations_rm10($db);
        } else {
            return $this->get_citations_rm9($db);
        }
    }

    /**
     * Get citations from RootsMagic 10
     *
     * @param PDO $db Database connection
     * @return array Array of citations
     */
    private function get_citations_rm10($db) {
        $query = "SELECT
            c.CitationId as uuid,
            c.SourceId as source_id,
            c.CitationRef as reference,
            c.CitationText as text,
            c.CitationActualText as actual_text,
            c.CitationDate as date,
            c.CitationComments as comments,
            cl.OwnerType as owner_type,
            cl.OwnerId as owner_id
        FROM Citation c
        LEFT JOIN CitationLink cl ON c.CitationId = cl.CitationId";
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Get citations from RootsMagic 9
     *
     * @param PDO $db Database connection
     * @return array Array of citations
     */
    private function get_citations_rm9($db) {
        $query = "SELECT
            c.CitationID as uuid,
            c.SourceID as source_id,
            c.RefNumber as reference,
            c.CitationText as text,
            c.ActualText as actual_text,
            c.CitationDate as date,
            c.CitationNotes as comments,
            cl.OwnerType as owner_type,
            cl.OwnerID as owner_id
        FROM CitationTable c
        LEFT JOIN CitationLinkTable cl ON c.CitationID = cl.CitationID";
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
    
    /**
     * Get media items from the database
     *
     * @param PDO $db Database connection
     * @return array Array of media items
     */
    public function get_media($db = null) {
        if (!$db && !extension_loaded('pdo_sqlite')) {
            throw new Exception('SQLite PDO extension is required');
        }
        
        if (!$db) {
            $db = new PDO("sqlite:{$this->file_path}");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        $version = $this->detect_version($db);
        
        if ($version === 10) {
            return $this->get_media_rm10($db);
        } else {
            return $this->get_media_rm9($db);
        }
    }
    
    /**
     * Get media items from RootsMagic 10
     *
     * @param PDO $db Database connection
     * @return array Array of media items
     */
    private function get_media_rm10($db) {
        $query = "SELECT 
            MediaId as uuid,
            MediaPath as file_path,
            MediaDesc as description,
            MediaRef as reference,
            MediaDate as date,
            MediaCaption as caption
        FROM Media";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $links_query = "SELECT LinkId, MediaId, OwnerType, OwnerId FROM MediaLink WHERE MediaId = :media_id";
            $links_stmt = $db->prepare($links_query);
            $links_stmt->execute(['media_id' => $row['uuid']]);
            $row['links'] = $links_stmt->fetchAll(PDO::FETCH_ASSOC);
            $result[] = $row;
        }
        return $result;
    }
    
    /**
     * Get media items from RootsMagic 9
     *
     * @param PDO $db Database connection
     * @return array Array of media items
     */
    private function get_media_rm9($db) {
        $query = "SELECT 
            MediaID as uuid,
            MediaPath as file_path,
            Description as description,
            RefNumber as reference,
            ChangeDate as date,
            Caption as caption
        FROM MultimediaTable";
        
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $links_query = "SELECT LinkID as id, MediaID as media_id, LinkType as owner_type, LinkID_Specified as owner_id FROM MultimediaLinkTable WHERE MediaID = :media_id";
            $links_stmt = $db->prepare($links_query);
            $links_stmt->execute(['media_id' => $row['uuid']]);
            $row['links'] = $links_stmt->fetchAll(PDO::FETCH_ASSOC);
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Get notes from the database
     *
     * @param PDO $db Database connection
     * @return array Array of notes
     */
    public function get_notes($db = null) {
        if (!$db && !extension_loaded('pdo_sqlite')) {
            throw new Exception('SQLite PDO extension is required');
        }
        if (!$db) {
            $db = new PDO("sqlite:{$this->file_path}");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        $version = $this->detect_version($db);
        if ($version === 10) {
            return $this->get_notes_rm10($db);
        } else {
            return $this->get_notes_rm9($db);
        }
    }

    /**
     * Get notes from RootsMagic 10
     *
     * @param PDO $db Database connection
     * @return array Array of notes
     */
    private function get_notes_rm10($db) {
        // RM10 uses NoteTable and links via NoteLinkTable
        $query = "SELECT
            n.NoteId as uuid,
            n.NoteName as name,
            n.NoteText as text,
            nl.OwnerType as owner_type,
            nl.OwnerId as owner_id
        FROM Note n
        LEFT JOIN NoteLink nl ON n.NoteId = nl.NoteId";
        $result = [];
        $stmt = $db->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Get notes from RootsMagic 9
     *
     * @param PDO $db Database connection
     * @return array Array of notes
     */
    private function get_notes_rm9($db) {
        // RM9 stores notes directly in various tables (e.g., EventTable.EventNote, NameTable.Note, etc.)
        // This requires a more complex approach, potentially querying multiple tables
        // For simplicity, we'll focus on notes linked to individuals (NameTable.Note) and events (EventTable.EventNote)
        // This is a simplified example and might need expansion for full note coverage in RM9
        
        $notes = [];

        // Individual notes
        $stmt_ind_notes = $db->query("SELECT OwnerID as owner_id, Note as text, 'I' as owner_type FROM NameTable WHERE Note IS NOT NULL AND Note != ''");
        while ($row = $stmt_ind_notes->fetch(PDO::FETCH_ASSOC)) {
            $row['uuid'] = uniqid('note_'); // Generate a unique ID for the note
            $notes[] = $row;
        }

        // Event notes
        $stmt_event_notes = $db->query("SELECT OwnerID as owner_id, EventID as event_id, EventNote as text, OwnerType as owner_type FROM EventTable WHERE EventNote IS NOT NULL AND EventNote != ''");
        while ($row = $stmt_event_notes->fetch(PDO::FETCH_ASSOC)) {
             $row['uuid'] = uniqid('note_'); // Generate a unique ID for the note
            // Determine if owner is Person or Family for consistency with RM10 structure
            if ($row['owner_type'] === 'I') { // Individual event
                 $row['owner_id'] = $row['owner_id']; // This is actually PersonID
            } elseif ($row['owner_type'] === 'F') { // Family event
                 $row['owner_id'] = $row['owner_id']; // This is FamilyID
            }
            // We can add event_id to the note if needed for context
            // $row['context_id'] = $row['event_id']; 
            unset($row['event_id']);
            $notes[] = $row;
        }
        // Potentially add other note sources from RM9 (FamilyTable notes, PlaceTable notes, etc.)
        return $notes;
    }

    /**
     * Import data from RootsMagic 10
     * 
     * @param PDO $db Database connection
     * @param array $options Import options
     * @return array Import statistics
     */
    private function import_rm10_data($db, $options = []) {
        $stats = [
            'individuals' => 0, 'families' => 0, 'events' => 0, 'places' => 0,
            'sources' => 0, 'citations' => 0, 'media' => 0, 'notes' => 0
        ];

        // Import individuals
        foreach ($this->get_individuals_rm10($db) as $data) {
            Individual::create($this->map_rm10_person($data));
            $stats['individuals']++;
        }
        // Import families
        foreach ($this->get_families_rm10($db) as $data) {
            Family::create($this->map_rm10_family($data));
            $stats['families']++;
        }
        // Import events
        foreach ($this->get_events_rm10($db) as $data) {
            Event::create($this->map_rm10_event($data));
            $stats['events']++;
        }
        // Import places
        foreach ($this->get_places_rm10($db) as $data) {
            Place::create($this->map_rm10_place($data));
            $stats['places']++;
        }
        // Import sources
        foreach ($this->get_sources_rm10($db) as $data) {
            Source::create($this->map_rm10_source($data));
            $stats['sources']++;
        }
        // Import citations (and link to owners)
        // ... (implementation for citations needed) ...
        // Import media
        // ... (implementation for media needed) ...
        // Import notes (and link to owners)
        // ... (implementation for notes needed) ...
        
        return $stats;
    }
    
    /**
     * Import data from RootsMagic 9
     * 
     * @param PDO $db Database connection
     * @param array $options Import options
     * @return array Import statistics
     */
    private function import_rm9_data($db, $options = []) {
         $stats = [
            'individuals' => 0, 'families' => 0, 'events' => 0, 'places' => 0,
            'sources' => 0, 'citations' => 0, 'media' => 0, 'notes' => 0
        ];
        
        // Import individuals
        foreach ($this->get_individuals_rm9($db) as $data) {
            Individual::create($this->map_rm9_person($data));
            $stats['individuals']++;
        }
        // Import families
        foreach ($this->get_families_rm9($db) as $data) {
            Family::create($this->map_rm9_family($data));
            $stats['families']++;
        }
        // Import events
        foreach ($this->get_events_rm9($db) as $data) {
            Event::create($this->map_rm9_event($data));
            $stats['events']++;
        }
        // Import places
        foreach ($this->get_places_rm9($db) as $data) {
            Place::create($this->map_rm9_place($data));
            $stats['places']++;
        }
        // Import sources
        foreach ($this->get_sources_rm9($db) as $data) {
            Source::create($this->map_rm9_source($data));
            $stats['sources']++;
        }
        // Import citations (and link to owners)
        // ... (implementation for citations needed) ...
        // Import media
        // ... (implementation for media needed) ...
        // Import notes (and link to owners)
        // ... (implementation for notes needed) ...

        return $stats;
    }

    // --- Data Mapping Methods ---
    private function map_gender($rm_gender) {
        // Simplified mapping, expand as needed
        if ($rm_gender === 'M') return 'Male';
        if ($rm_gender === 'F') return 'Female';
        return 'Unknown';
    }

    private function map_rm10_person($data) {
        return [
            'given_name' => $data['given_name'] ?? '', // Corrected from given_names
            'surname' => $data['surname'] ?? '',
            'sex' => $this->map_gender($data['sex'] ?? null),
            'birth_date' => $data['birth_date'] ?? null,
            'death_date' => $data['death_date'] ?? null,
            'is_private' => (bool)($data['is_private'] ?? false),
            'uuid' => $data['uuid'] ?? null,
        ];
    }

    private function map_rm9_person($data) {
        return [
            'given_name' => $data['given_name'] ?? '',
            'surname' => $data['surname'] ?? '',
            'sex' => $this->map_gender($data['sex'] ?? null),
            'birth_date' => $data['birth_date'] ?? null,
            'death_date' => $data['death_date'] ?? null,
            'is_private' => (bool)($data['is_private'] ?? false),
            'uuid' => $data['uuid'] ?? null,
        ];
    }

    private function map_rm10_family($data) {
        return [
            'husband_id' => $data['husband_id'] ?? null,
            'wife_id' => $data['wife_id'] ?? null,
            'marriage_date' => $data['marriage_date'] ?? null,
            'marriage_place' => $data['marriage_place'] ?? null,
            'uuid' => $data['uuid'] ?? null,
        ];
    }

    private function map_rm9_family($data) {
        return [
            'husband_id' => $data['husband_id'] ?? null,
            'wife_id' => $data['wife_id'] ?? null,
            'marriage_date' => $data['marriage_date'] ?? null,
            'marriage_place' => $data['marriage_place'] ?? null,
            'uuid' => $data['uuid'] ?? null,
        ];
    }

    private function map_rm10_event($data) {
        return [
            'type' => $data['type'] ?? '',
            'date' => $data['date'] ?? null,
            'place' => $data['place'] ?? null,
            'description' => $data['description'] ?? null,
            'person_id' => $data['person_id'] ?? null,
            'family_id' => $data['family_id'] ?? null,
            'uuid' => $data['uuid'] ?? null,
        ];
    }

    private function map_rm9_event($data) {
        return [
            'type' => $data['type'] ?? '',
            'date' => $data['date'] ?? null,
            'place' => $data['place'] ?? null,
            'description' => $data['description'] ?? null,
            'person_id' => $data['person_id'] ?? null,
            'family_id' => $data['family_id'] ?? null,
            'uuid' => $data['uuid'] ?? null,
        ];
    }

    private function map_rm10_place($data) {
        return [
            'name' => $data['name'] ?? '',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'uuid' => $data['uuid'] ?? null,
        ];
    }

    private function map_rm9_place($data) {
        return [
            'name' => $data['name'] ?? '',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'uuid' => $data['uuid'] ?? null,
        ];
    }

    private function map_rm10_source($data) {
        return [
            'title' => $data['title'] ?? '',
            'reference' => $data['reference'] ?? null,
            'comments' => $data['comments'] ?? null,
            'uuid' => $data['uuid'] ?? null,
        ];
    }

    private function map_rm9_source($data) {
        return [
            'title' => $data['title'] ?? '',
            'text' => $data['text'] ?? null,
            'uuid' => $data['uuid'] ?? null,
        ];
    }
    
    private function map_rm10_citation($data) {
        return [
            'source_id' => $data['source_id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'text' => $data['text'] ?? null,
            'actual_text' => $data['actual_text'] ?? null,
            'date' => $data['date'] ?? null,
            'comments' => $data['comments'] ?? null,
            'owner_type' => $data['owner_type'] ?? null,
            'owner_id' => $data['owner_id'] ?? null,
            'uuid' => $data['uuid'] ?? null,
        ];
    }

    private function map_rm9_citation($data) {
        return [
            'source_id' => $data['source_id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'text' => $data['text'] ?? null,
            'actual_text' => $data['actual_text'] ?? null,
            'date' => $data['date'] ?? null,
            'comments' => $data['comments'] ?? null,
            'owner_type' => $data['owner_type'] ?? null,
            'owner_id' => $data['owner_id'] ?? null,
            'uuid' => $data['uuid'] ?? null,
        ];
    }
    
    // --- Helper Methods ---

    /**
     * Detect the RootsMagic version from the database structure
     *
     * @param PDO $pdo Database connection
     * @return int|null Version number (9 or 10) or null if can't detect
     */
    private function detect_version($pdo) {
        try {
            // Check for RootsMagic 10 specific tables
            $rm10_check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='DatabaseInformation'");
            if ($rm10_check && $rm10_check->fetch()) {
                $version_stmt = $pdo->prepare("SELECT Value FROM DatabaseInformation WHERE Name='Version'");
                $version_stmt->execute();
                $version_info = $version_stmt->fetch(PDO::FETCH_ASSOC);
                if ($version_info && strpos($version_info['Value'], '10') === 0) {
                    $this->version = 10;
                    return 10;
                }
            }

            // Check for RootsMagic 9 specific tables
            $rm9_check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='TreeInfo'");
            if ($rm9_check && $rm9_check->fetch()) {
                $version_stmt = $pdo->prepare("SELECT Version FROM TreeInfo LIMIT 1");
                $version_stmt->execute();
                $version_info = $version_stmt->fetch(PDO::FETCH_ASSOC);
                if ($version_info && strpos($version_info['Version'], '9') === 0) {
                    $this->version = 9;
                    return 9;
                }
            }

            $this->version = null;
            return null;
        } catch (PDOException $e) {
            $this->version = null;
            return null;
        }
    }
}
