<?php
/**
 * Database Manager Class
 *
 * @package HeritagePress
 */

namespace HeritagePress\Database;

class Database_Manager {
    /**
     * The database update manager
     */
    private $upgrade_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->upgrade_manager = new DatabaseUpgradeManager();
    }

    /**
     * Create the required database tables
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_prefix = $wpdb->prefix . 'heritage_press_';

        // Define table schemas

        $sql = array();

        // GEDCOM Trees table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}gedcom_trees (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, /* Added UNSIGNED */
            tree_id varchar(36) NOT NULL, /* This is the UUID for the tree, used as file_id in other tables */
            file_name varchar(255) NOT NULL,
            title varchar(255) NULL,
            description text NULL,
            character_set VARCHAR(50) NULL, /* HEAD.CHAR */
            gedcom_version VARCHAR(10) NULL, /* HEAD.GEDC.VERS */
            gedcom_form VARCHAR(50) NULL, /* HEAD.GEDC.FORM */
            source_product_id VARCHAR(255) NULL, /* HEAD.SOUR */
            source_product_version VARCHAR(50) NULL, /* HEAD.SOUR.VERS */
            source_product_name VARCHAR(255) NULL, /* HEAD.SOUR.NAME */
            source_product_corp VARCHAR(255) NULL, /* HEAD.SOUR.CORP */
            source_database_name VARCHAR(255) NULL, /* HEAD.SOUR.DATA */
            source_database_date VARCHAR(50) NULL, /* HEAD.SOUR.DATA.DATE */
            primary_submitter_id BIGINT(20) UNSIGNED NULL, /* Links to submitters table */
            destination_system_id VARCHAR(255) NULL, /* HEAD.DEST */
            default_place_format_template TEXT NULL, /* HEAD.PLAC.FORM */
            default_language_code VARCHAR(10) NULL, /* HEAD.LANG */
            version int NOT NULL DEFAULT 1,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active', 'archived') DEFAULT 'active',
            meta longtext NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tree_id (tree_id),
            KEY status (status)
        ) $charset_collate;";

        // Submitters table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}submitters (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL, /* Corresponds to gedcom_tree_id */
            name varchar(255) NULL,
            address_place_id bigint(20) UNSIGNED NULL, /* Links to places table for ADDR structure */
            phone varchar(50) NULL,
            email varchar(255) NULL,
            fax varchar(50) NULL,
            www varchar(255) NULL,
            language_preferences TEXT NULL, /* List of LANG tags */
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY name (name)
        ) $charset_collate;";

        // Shared Notes table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}shared_notes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL, /* Corresponds to gedcom_tree_id */
            text longtext NULL,
            language_code varchar(10) NULL, /* LANG */
            mime_type varchar(50) NULL, /* MIME (text/plain, text/html) */
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id)
        ) $charset_collate;";

        // Individuals table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}individuals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL, /* Corresponds to gedcom_tree_id */
            given_names varchar(255) NULL, /* For primary/display name */
            surname varchar(255) NULL, /* For primary/display name */
            title varchar(100) NULL, /* For primary/display name INDI.TITL */
            sex ENUM('M', 'F', 'X', 'U') NULL, /* GEDCOM SEX tag (M, F, X, U) */
            birth_date varchar(50) NULL,
            birth_place_id bigint(20) UNSIGNED NULL,
            death_date varchar(50) NULL,
            death_place_id bigint(20) UNSIGNED NULL,
            cause_of_death varchar(255) NULL,
            burial_date varchar(50) NULL,
            burial_location_id bigint(20) UNSIGNED NULL,
            main_media_id bigint(20) UNSIGNED NULL,
            occupation varchar(255) NULL, /* OCCU */
            education varchar(255) NULL, /* EDUC */
            religion varchar(255) NULL, /* RELI */
            nationality varchar(255) NULL, /* NATI */
            ancestor_interest TINYINT(1) DEFAULT 0, /* ANCI */
            descendant_interest TINYINT(1) DEFAULT 0, /* DESI */
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes longtext NULL, /* For direct, non-shared notes */
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            privacy tinyint(1) DEFAULT 0,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime NULL, /* For soft deletes */
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY birth_date (birth_date),
            KEY death_date (death_date),
            KEY surname (surname),
            KEY birth_place (birth_place_id),
            KEY death_place (death_place_id),
            KEY name_search (surname, given_names),
            KEY privacy_status (privacy, status)
        ) $charset_collate;";

        // Individual Names table (for INDI.NAME structure)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}individual_names (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            individual_id bigint(20) UNSIGNED NOT NULL,
            name_type ENUM('AKA', 'BIRTH', 'IMMIGRANT', 'MAIDEN', 'MARRIED', 'PROFESSIONAL', 'OTHER') NULL, /* INDI.NAME.TYPE */
            name_text_full varchar(512) NULL, /* Full name string if provided */
            name_prefix varchar(100) NULL, /* NPFX */
            given_name varchar(255) NULL, /* GIVN */
            nickname varchar(255) NULL, /* NICK */
            surname_prefix varchar(100) NULL, /* SPFX */
            surname varchar(255) NULL, /* SURN */
            name_suffix varchar(100) NULL, /* NSFX */
            language_code varchar(10) NULL, /* LANG for this name representation */
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes longtext NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY individual_id (individual_id),
            KEY name_type (name_type),
            KEY surname_given (surname, given_name)
        ) $charset_collate;";

        // Families table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}families (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL, /* Corresponds to gedcom_tree_id */
            husband_id bigint(20) UNSIGNED NULL,
            wife_id bigint(20) UNSIGNED NULL,
            marriage_date varchar(50) NULL,
            marriage_place_id bigint(20) UNSIGNED NULL,
            divorce_date varchar(50) NULL,
            divorce_place_id bigint(20) UNSIGNED NULL,
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes longtext NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            privacy tinyint(1) DEFAULT 0,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime NULL, /* For soft deletes */
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY husband_id (husband_id),
            KEY wife_id (wife_id)
        ) $charset_collate;";

        // Events table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}events (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL, /* Corresponds to gedcom_tree_id */
            individual_id bigint(20) UNSIGNED NULL,
            family_id bigint(20) UNSIGNED NULL,
            event_tag varchar(10) NULL,
            event_type_detail varchar(255) NULL, /* For EVEN.TYPE */
            event_date varchar(50) NULL,
            date_phrase varchar(255) NULL, /* For DATE.PHRASE */
            sort_date varchar(50) NULL, /* SDATE */
            place_id bigint(20) UNSIGNED NULL,
            description text NULL,
            age_at_event varchar(50) NULL, /* AGE */
            cause_of_event varchar(255) NULL, /* CAUS */
            responsible_agency varchar(255) NULL, /* AGNC */
            religion varchar(255) NULL, /* RELI associated with event */
            temple_code varchar(10) NULL, /* LDS TEMP tag */
            event_status ENUM('BIC', 'CANCELED', 'CHILD', 'COMPLETED', 'EXCLUDED', 'DNS', 'DNS_CAN', 'INFANT', 'PRE_1970', 'STILLBORN', 'SUBMITTED', 'UNCLEARED') NULL, /* LDS STAT tag and other event statuses */
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes longtext NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            privacy tinyint(1) DEFAULT 0,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY individual_id (individual_id),
            KEY family_id (family_id),
            KEY event_tag (event_tag),
            KEY place_id (place_id),
            KEY temple_code (temple_code),
            KEY event_status (event_status)
        ) $charset_collate;";

        // Places table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}places (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            name varchar(255) NOT NULL, /* Primary name/lowest jurisdiction */
            street_address varchar(255) NULL, /* ADDR substructure */
            city varchar(100) NULL, /* ADDR.CITY */
            state_province varchar(100) NULL, /* ADDR.STAE */
            postal_code varchar(50) NULL, /* ADDR.POST */
            country varchar(100) NULL, /* ADDR.CTRY */
            latitude decimal(10,8) NULL,
            longitude decimal(11,8) NULL,
            parent_id bigint(20) UNSIGNED NULL,
            language_code varchar(10) NULL, /* LANG for this place name */
            place_format_template TEXT NULL, /* PLAC.FORM */
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes text NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY name (name)
        ) $charset_collate;";

        // Family Children table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}family_children (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            family_id bigint(20) UNSIGNED NOT NULL,
            child_id bigint(20) UNSIGNED NOT NULL,
            relationship_type ENUM('ADOPTED', 'BIRTH', 'FOSTER', 'SEALING', 'OTHER') DEFAULT 'BIRTH', /* PEDI */
            adoption_type ENUM('HUSB', 'WIFE', 'BOTH') NULL, /* FAMC.ADOP */
            status ENUM('CHALLENGED', 'DISPROVEN', 'PROVEN') NULL, /* FAMC.STAT */
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes text NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_family_child (family_id, child_id),
            KEY family_id (family_id),
            KEY child_id (child_id),
            CONSTRAINT fk_family_children_family FOREIGN KEY (family_id) REFERENCES {$table_prefix}families(id) ON DELETE CASCADE,
            CONSTRAINT fk_family_children_child FOREIGN KEY (child_id) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Media table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}media (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL, /* Corresponds to gedcom_tree_id */
            title varchar(255) NULL, /* TITL */
            description text NULL,
            file_path varchar(255) NULL, /* FILE */
            mime_type varchar(100) NULL, /* FORM */
            original_medium ENUM('AUDIO', 'BOOK', 'CARD', 'ELECTRONIC', 'FICHE', 'FILM', 'MAGAZINE', 'MANUSCRIPT', 'MAP', 'NEWSPAPER', 'PHOTO', 'TOMBSTONE', 'VIDEO', 'OTHER') NULL, /* FILE.FORM.MEDI */
            crop_left INT NULL, /* CROP.LEFT */
            crop_top INT NULL, /* CROP.TOP */
            crop_width INT NULL, /* CROP.WIDTH */
            crop_height INT NULL, /* CROP.HEIGHT */
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes longtext NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            privacy tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id)
        ) $charset_collate;";

        // Sources table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}sources (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            source_type ENUM('ORIGINAL', 'DERIVATIVE', 'AUTHORED') NOT NULL,
            title varchar(255) NOT NULL,
            author varchar(255) NULL,
            publication_info text NULL,
            repository_name varchar(255) NULL,
            call_number varchar(100) NULL,
            url varchar(255) NULL,
            accessed_date date NULL,
            notes text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY source_type (source_type),
            KEY title (title)
        ) $charset_collate;";

        // Citations table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}citations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            source_id bigint(20) UNSIGNED NOT NULL,
            citation_type ENUM('FIRST', 'SUBSEQUENT', 'BIBLIOGRAPHY') NOT NULL,
            first_ref_citation_id bigint(20) UNSIGNED NULL,
            page_info varchar(255) NULL,
            detail_info text NULL,
            formatted_text text NOT NULL,
            notes text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY source_id (source_id),
            KEY citation_type (citation_type),
            KEY first_ref_citation_id (first_ref_citation_id),
            CONSTRAINT fk_citations_source FOREIGN KEY (source_id) 
                REFERENCES {$table_prefix}sources(id) ON DELETE CASCADE,
            CONSTRAINT fk_citations_first_ref FOREIGN KEY (first_ref_citation_id) 
                REFERENCES {$table_prefix}citations(id) ON DELETE SET NULL
        ) $charset_collate;";

        // Citation References table (for linking citations to entities)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}citation_references (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            citation_id bigint(20) UNSIGNED NOT NULL,
            entity_type ENUM('individual', 'family', 'event', 'place', 'media') NOT NULL,
            entity_id bigint(20) UNSIGNED NOT NULL,
            reference_type varchar(50) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_reference (citation_id, entity_type, entity_id),
            KEY citation_id (citation_id),
            KEY entity_reference (entity_type, entity_id),
            CONSTRAINT fk_citation_references_citation FOREIGN KEY (citation_id) 
                REFERENCES {$table_prefix}citations(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Add foreign key constraints
        // (Ensure all FKs, including new ones for shared_notes, submitters, individual_names, etc. are defined here)
        // ... (existing FKs will be here, need to add new ones)

        $sql[] = "ALTER TABLE {$table_prefix}gedcom_trees
            ADD CONSTRAINT fk_gedcom_trees_submitter FOREIGN KEY (primary_submitter_id) REFERENCES {$table_prefix}submitters(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}submitters
            ADD CONSTRAINT fk_submitters_address_place FOREIGN KEY (address_place_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL;";
            
        $sql[] = "ALTER TABLE {$table_prefix}individuals
            ADD CONSTRAINT fk_individuals_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_individuals_birth_place FOREIGN KEY (birth_place_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_individuals_death_place FOREIGN KEY (death_place_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_individuals_burial_place FOREIGN KEY (burial_location_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_individuals_main_media FOREIGN KEY (main_media_id) REFERENCES {$table_prefix}media(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}individual_names
            ADD CONSTRAINT fk_individual_names_individual FOREIGN KEY (individual_id) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE,
            ADD CONSTRAINT fk_individual_names_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}families
            ADD CONSTRAINT fk_families_husband FOREIGN KEY (husband_id) REFERENCES {$table_prefix}individuals(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_families_wife FOREIGN KEY (wife_id) REFERENCES {$table_prefix}individuals(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_families_marriage_place FOREIGN KEY (marriage_place_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_families_divorce_place FOREIGN KEY (divorce_place_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_families_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}events
            ADD CONSTRAINT fk_events_individual FOREIGN KEY (individual_id) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE,
            ADD CONSTRAINT fk_events_family FOREIGN KEY (family_id) REFERENCES {$table_prefix}families(id) ON DELETE CASCADE,
            ADD CONSTRAINT fk_events_place FOREIGN KEY (place_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_events_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}places
            ADD CONSTRAINT fk_places_parent FOREIGN KEY (parent_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_places_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";
            
        $sql[] = "ALTER TABLE {$table_prefix}family_children
            ADD CONSTRAINT fk_family_children_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}media
            ADD CONSTRAINT fk_media_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}sources
            ADD CONSTRAINT fk_sources_repository FOREIGN KEY (repository_id) REFERENCES {$table_prefix}repositories(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_sources_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}repositories
            ADD CONSTRAINT fk_repositories_address_place FOREIGN KEY (address_place_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL,
            ADD CONSTRAINT fk_repositories_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";
            
        $sql[] = "ALTER TABLE {$table_prefix}citations
            ADD CONSTRAINT fk_citations_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";
            // Note: FKs for fact_id in citations to specific fact tables would be complex and might require triggers or application-level logic,
            // or separate linking tables if strict FKs are needed for polymorphic associations.
            // For now, fact_id and fact_table_type are for application-level joins.

        $sql[] = "ALTER TABLE {$table_prefix}media_relationships
            ADD CONSTRAINT fk_media_relationships_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}individual_identifiers
            ADD CONSTRAINT fk_individual_identifiers_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";
            
        $sql[] = "ALTER TABLE {$table_prefix}individual_facts
            ADD CONSTRAINT fk_individual_facts_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}family_facts
            ADD CONSTRAINT fk_family_facts_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";

        $sql[] = "ALTER TABLE {$table_prefix}associations
            ADD CONSTRAINT fk_associations_shared_note FOREIGN KEY (shared_note_id) REFERENCES {$table_prefix}shared_notes(id) ON DELETE SET NULL;";

        foreach ($sql as $query) {
            $wpdb->query($query);
        }
    }

    /**
     * Get the plugin table prefix
     */
    public static function get_table_prefix() {
        global $wpdb;
        return $wpdb->prefix . 'heritage_press_';
    }

    /**
     * Run database updates if needed
     */
    public function maybe_update_database() {
        $this->upgrade_manager->maybe_update();
    }
}
