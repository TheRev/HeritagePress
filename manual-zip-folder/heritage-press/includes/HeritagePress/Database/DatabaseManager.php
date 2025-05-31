<?php
/**
 * Database Manager Class
 *
 * @package HeritagePress
 */

namespace HeritagePress\Database;

class DatabaseManager {
    /**
     * Get database table prefix
     */
    public static function get_table_prefix() { // Made public and static
        global $wpdb;
        return $wpdb->prefix . 'heritage_press_';
    }

    /**
     * Get table creation SQL
     */
    public function get_table_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = self::get_table_prefix(); // Changed to self::

        $sql = array();

        // Individuals table
        $sql[] = "CREATE TABLE {$prefix}individuals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            given_names varchar(255),
            surname varchar(255),
            birth_date date,
            birth_place_id bigint(20),
            death_date date,
            death_place_id bigint(20),
            gender varchar(1),
            privacy tinyint(1) DEFAULT 0,
            notes longtext,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY birth_date (birth_date),
            KEY death_date (death_date),
            KEY surname (surname)
        ) $charset_collate;";

        // Families table
        $sql[] = "CREATE TABLE {$prefix}families (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            husband_id bigint(20),
            wife_id bigint(20),
            marriage_date date,
            marriage_place_id bigint(20),
            divorce_date date,
            divorce_place_id bigint(20),
            notes longtext,
            privacy tinyint(1) DEFAULT 0,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY husband_id (husband_id),
            KEY wife_id (wife_id)
        ) $charset_collate;";

        // Events table
        $sql[] = "CREATE TABLE {$prefix}events (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            individual_id bigint(20),
            family_id bigint(20),
            type varchar(50) NOT NULL,
            date date,
            place_id bigint(20),
            description text,
            privacy tinyint(1) DEFAULT 0,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY individual_id (individual_id),
            KEY family_id (family_id),
            KEY type (type)
        ) $charset_collate;";

        // Places table
        $sql[] = "CREATE TABLE {$prefix}places (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            name varchar(255) NOT NULL,
            latitude decimal(10,8),
            longitude decimal(11,8),
            parent_id bigint(20),
            notes text,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY name (name)
        ) $charset_collate;";

        // Family Children table for many-to-many relationships
        $sql[] = "CREATE TABLE {$prefix}family_children (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            family_id bigint(20) NOT NULL,
            child_id bigint(20) NOT NULL,
            relationship_type varchar(50) DEFAULT 'birth',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_family_child (family_id, child_id),
            KEY family_id (family_id),
            KEY child_id (child_id),
            CONSTRAINT fk_family_children_family FOREIGN KEY (family_id) REFERENCES {$prefix}families(id) ON DELETE CASCADE,
            CONSTRAINT fk_family_children_child FOREIGN KEY (child_id) REFERENCES {$prefix}individuals(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Media table
        $sql[] = "CREATE TABLE {$prefix}media (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            title varchar(255),
            description text,
            file_path varchar(255),
            mime_type varchar(100),
            privacy tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY uuid (uuid)
        ) $charset_collate;";

        // Sources table
        $sql[] = "CREATE TABLE {$prefix}sources (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            title varchar(255) NOT NULL,
            author varchar(255),
            publication_info text,
            repository varchar(255),
            call_number varchar(100),
            type varchar(50) NOT NULL,
            url varchar(255),
            notes text,
            date date,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY type (type),
            KEY date (date)
        ) $charset_collate;";

        // Repositories table
        $sql[] = "CREATE TABLE {$prefix}repositories (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            file_id varchar(36) NOT NULL,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            address text,
            website varchar(255),
            contact_info text,
            access_notes text,
            notes text,
            status enum('active', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY uuid (uuid),
            KEY file_id (file_id),
            KEY status (status),
            KEY type (type),
            KEY name (name)
        ) $charset_collate;";

        // GEDCOM Trees table
        $sql[] = "CREATE TABLE {$prefix}gedcom_trees (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tree_id varchar(36) NOT NULL,
            file_name varchar(255) NOT NULL,
            title varchar(255),
            description text,
            version int NOT NULL DEFAULT 1,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active', 'archived') DEFAULT 'active',
            meta longtext,
            PRIMARY KEY (id),
            UNIQUE KEY tree_id (tree_id),
            KEY status (status)
        ) $charset_collate;";

        return $sql;
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $sql_queries = $this->get_table_schema();
        foreach ($sql_queries as $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Drop database tables
     */
    public function drop_tables() {
        global $wpdb;
        $prefix = $this->get_table_prefix();

        $tables = array(
            'individuals',
            'families',
            'events',
            'places',
            'media',
            'sources',
            'citations',
            'repositories',
            'gedcom_trees',
            'family_children'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
        }
    }
}
