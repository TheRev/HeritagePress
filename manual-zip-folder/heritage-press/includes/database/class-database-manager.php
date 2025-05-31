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
        $sql[] = "CREATE TABLE {$table_prefix}gedcom_trees (
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
        $sql[] = "CREATE TABLE {$table_prefix}submitters (
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
        $sql[] = "CREATE TABLE {$table_prefix}shared_notes (
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
        $sql[] = "CREATE TABLE {$table_prefix}individuals (
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
        $sql[] = "CREATE TABLE {$table_prefix}individual_names (
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
        $sql[] = "CREATE TABLE {$table_prefix}families (
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
        $sql[] = "CREATE TABLE {$table_prefix}events (
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
        $sql[] = "CREATE TABLE {$table_prefix}places (
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
        $sql[] = "CREATE TABLE {$table_prefix}family_children (
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
        $sql[] = "CREATE TABLE {$table_prefix}media (
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
        $sql[] = "CREATE TABLE {$table_prefix}sources (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL, /* Corresponds to gedcom_tree_id */
            title varchar(255) NOT NULL, /* TITL */
            author varchar(255) NULL, /* AUTH */
            publication_info text NULL, /* PUBL */
            agency varchar(255) NULL, /* AGNC */
            repository_id bigint(20) UNSIGNED NULL,
            call_number varchar(100) NULL, /* CALN */
            repository_item_medium ENUM('AUDIO', 'BOOK', 'CARD', 'ELECTRONIC', 'FICHE', 'FILM', 'MAGAZINE', 'MANUSCRIPT', 'MAP', 'NEWSPAPER', 'PHOTO', 'TOMBSTONE', 'VIDEO', 'OTHER') NULL, /* CALN.MEDI */
            type varchar(50) NOT NULL, /* User-defined category, not GEDCOM TYPE */
            url varchar(255) NULL, /* WWW */
            recorded_event_types TEXT NULL, /* SOUR.DATA.EVEN */
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes text NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            date varchar(50) NULL, /* SOUR.DATA.DATE */
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY type (type),
            KEY date (date),
            KEY repository_id (repository_id)
        ) $charset_collate;";

        // Repositories table
        $sql[] = "CREATE TABLE {$table_prefix}repositories (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL, /* Corresponds to gedcom_tree_id */
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL, /* User-defined category */
            address_place_id bigint(20) UNSIGNED NULL, /* Links to places table for ADDR structure */
            website varchar(255) NULL, /* WWW */
            contact_info text NULL, /* Can include email, phone */
            access_notes text NULL,
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes text NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY type (type),
            KEY name (name)
        ) $charset_collate;";

        // Citations table (Source-Data Links)
        $sql[] = "CREATE TABLE {$table_prefix}citations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL, /* Corresponds to gedcom_tree_id */
            source_id bigint(20) UNSIGNED NOT NULL,
            individual_id bigint(20) UNSIGNED NULL,
            family_id bigint(20) UNSIGNED NULL,
            event_id bigint(20) UNSIGNED NULL,
            place_id bigint(20) UNSIGNED NULL,
            media_id bigint(20) UNSIGNED NULL, /* Link citation to media */
            fact_id bigint(20) UNSIGNED NULL, /* Link citation to a fact in individual_facts or family_facts */
            fact_table_type ENUM('individual_facts', 'family_facts', 'individual_names', 'associations') NULL, /* To identify which fact table fact_id refers to */
            page varchar(100) NULL, /* PAGE */
            quality_score tinyint NULL, /* QUAY ('0', '1', '2', '3') */
            role_in_source ENUM('CHIL', 'CLERGY', 'FATH', 'FRIEND', 'GODP', 'HUSB', 'MOTH', 'MULTIPLE', 'NGHBR', 'OFFICIATOR', 'PARENT', 'SPOU', 'WIFE', 'WITN', 'OTHER') NULL, /* SOUR.EVEN.ROLE */
            source_text_actual TEXT NULL, /* SOUR.TEXT */
            citation_text text NULL, /* User\'s summary/text of citation */
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes text NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY source_id (source_id),
            KEY individual_id (individual_id),
            KEY family_id (family_id),
            KEY event_id (event_id),
            KEY place_id (place_id),
            KEY media_id (media_id),
            KEY fact_ref (fact_id, fact_table_type),
            CONSTRAINT fk_citations_source FOREIGN KEY (source_id) REFERENCES {$table_prefix}sources(id) ON DELETE CASCADE,
            CONSTRAINT fk_citations_individual FOREIGN KEY (individual_id) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE,
            CONSTRAINT fk_citations_family FOREIGN KEY (family_id) REFERENCES {$table_prefix}families(id) ON DELETE CASCADE,
            CONSTRAINT fk_citations_event FOREIGN KEY (event_id) REFERENCES {$table_prefix}events(id) ON DELETE CASCADE,
            CONSTRAINT fk_citations_place FOREIGN KEY (place_id) REFERENCES {$table_prefix}places(id) ON DELETE CASCADE,
            CONSTRAINT fk_citations_media FOREIGN KEY (media_id) REFERENCES {$table_prefix}media(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Media Relationships table
        $sql[] = "CREATE TABLE {$table_prefix}media_relationships (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            media_id bigint(20) UNSIGNED NOT NULL,
            individual_id bigint(20) UNSIGNED NULL,
            family_id bigint(20) UNSIGNED NULL,
            event_id bigint(20) UNSIGNED NULL,
            place_id bigint(20) UNSIGNED NULL,
            source_id bigint(20) UNSIGNED NULL, /* If media is directly linked as a source itself, or describes a source */
            fact_id bigint(20) UNSIGNED NULL, /* Link media to a fact */
            fact_table_type ENUM('individual_facts', 'family_facts', 'individual_names', 'associations') NULL,
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes text NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY media_id (media_id),
            KEY individual_id (individual_id),
            KEY family_id (family_id),
            KEY event_id (event_id),
            KEY place_id (place_id),
            KEY source_id (source_id),
            KEY fact_ref (fact_id, fact_table_type),
            CONSTRAINT fk_media_rel_media FOREIGN KEY (media_id) REFERENCES {$table_prefix}media(id) ON DELETE CASCADE,
            CONSTRAINT fk_media_rel_individual FOREIGN KEY (individual_id) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE,
            CONSTRAINT fk_media_rel_family FOREIGN KEY (family_id) REFERENCES {$table_prefix}families(id) ON DELETE CASCADE,
            CONSTRAINT fk_media_rel_event FOREIGN KEY (event_id) REFERENCES {$table_prefix}events(id) ON DELETE CASCADE,
            CONSTRAINT fk_media_rel_place FOREIGN KEY (place_id) REFERENCES {$table_prefix}places(id) ON DELETE CASCADE,
            CONSTRAINT fk_media_rel_source FOREIGN KEY (source_id) REFERENCES {$table_prefix}sources(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Individual Identifiers table
        $sql[] = "CREATE TABLE {$table_prefix}individual_identifiers (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            individual_id bigint(20) UNSIGNED NOT NULL,
            identifier_type varchar(255) NOT NULL, /* e.g., SSN, NationalID, URI from EXID.TYPE, user-defined from IDNO.TYPE */
            identifier_value varchar(255) NOT NULL,
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes text NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY individual_id (individual_id),
            KEY identifier_type (identifier_type),
            CONSTRAINT fk_individual_identifiers_individual FOREIGN KEY (individual_id) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Individual Facts table
        $sql[] = "CREATE TABLE {$table_prefix}individual_facts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            individual_id bigint(20) UNSIGNED NOT NULL,
            fact_tag varchar(10) NOT NULL, /* e.g., FACT, CAST, DSCR, PROP, RESI, NO (for non-event) */
            fact_type_detail varchar(255) NULL, /* From the TYPE substructure of FACT, or event type for NO */
            fact_value text NULL,
            fact_date varchar(50) NULL,
            date_phrase varchar(255) NULL,
            sort_date varchar(50) NULL,
            place_id bigint(20) UNSIGNED NULL,
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes longtext NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            privacy tinyint(1) DEFAULT 0,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY individual_id (individual_id),
            KEY fact_tag (fact_tag),
            KEY place_id (place_id),
            CONSTRAINT fk_individual_facts_individual FOREIGN KEY (individual_id) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE,
            CONSTRAINT fk_individual_facts_place FOREIGN KEY (place_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL
        ) $charset_collate;";

        // Family Facts table
        $sql[] = "CREATE TABLE {$table_prefix}family_facts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            family_id bigint(20) UNSIGNED NOT NULL,
            fact_tag varchar(10) NOT NULL, /* e.g., FACT, RESI, NCHI (though NCHI often derived) */
            fact_type_detail varchar(255) NULL,
            fact_value text NULL,
            fact_date varchar(50) NULL,
            date_phrase varchar(255) NULL,
            sort_date varchar(50) NULL,
            place_id bigint(20) UNSIGNED NULL,
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes longtext NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            privacy tinyint(1) DEFAULT 0,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY family_id (family_id),
            KEY fact_tag (fact_tag),
            KEY place_id (place_id),
            CONSTRAINT fk_family_facts_family FOREIGN KEY (family_id) REFERENCES {$table_prefix}families(id) ON DELETE CASCADE,
            CONSTRAINT fk_family_facts_place FOREIGN KEY (place_id) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL
        ) $charset_collate;";

        // Associations table (for ASSO structure)
        $sql[] = "CREATE TABLE {$table_prefix}associations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            individual_id_1 bigint(20) UNSIGNED NOT NULL, /* The individual having the association */
            individual_id_2 bigint(20) UNSIGNED NOT NULL, /* The associated individual */
            association_type ENUM('CHIL', 'CLERGY', 'FATH', 'FRIEND', 'GODP', 'HUSB', 'MOTH', 'MULTIPLE', 'NGHBR', 'OFFICIATOR', 'PARENT', 'SPOU', 'WIFE', 'WITN', 'OTHER') NULL, /* ROLE from ASSO.ROLE */
            phrase_for_role varchar(255) NULL, /* PHRASE for ASSO.ROLE.PHRASE */
            date_of_association varchar(50) NULL,
            place_id_association bigint(20) UNSIGNED NULL,
            user_reference_text varchar(255) NULL, /* REFN */
            restriction_type SET('CONFIDENTIAL', 'LOCKED', 'PRIVACY') NULL, /* RESN */
            notes text NULL,
            shared_note_id bigint(20) UNSIGNED NULL, /* FK to shared_notes */
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY file_id (file_id),
            KEY individual_id_1 (individual_id_1),
            KEY individual_id_2 (individual_id_2),
            KEY association_type (association_type),
            CONSTRAINT fk_associations_individual1 FOREIGN KEY (individual_id_1) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE,
            CONSTRAINT fk_associations_individual2 FOREIGN KEY (individual_id_2) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE,
            CONSTRAINT fk_associations_place FOREIGN KEY (place_id_association) REFERENCES {$table_prefix}places(id) ON DELETE SET NULL
        ) $charset_collate;";

        // Audit Logs table
        $sql[] = "CREATE TABLE {$table_prefix}audit_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            file_id varchar(36) NULL, /* Corresponds to gedcom_tree_id, if applicable */
            user_id bigint(20) UNSIGNED NULL,
            action ENUM('CREATE', 'UPDATE', 'DELETE', 'RESTORE', 'FORCE_DELETE', 'LOGIN_SUCCESS', 'LOGIN_FAIL', 'SYSTEM') NOT NULL,
            entity_table varchar(100) NULL, /* e.g., 'individuals', 'families', 'users' */
            entity_id bigint(20) UNSIGNED NULL, /* ID of the affected entity */
            entity_uuid varchar(36) NULL, /* UUID of the affected entity, if any */
            changed_fields longtext NULL, /* JSON detailing changes: { field: { old: value, new: value } } */
            description text NULL, /* Optional textual description of the event */
            ip_address varchar(100) NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY file_id (file_id),
            KEY user_id (user_id),
            KEY action (action),
            KEY entity_table_id (entity_table, entity_id),
            KEY timestamp (timestamp)
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
