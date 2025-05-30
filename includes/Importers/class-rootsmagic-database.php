<?php
/**
 * RootsMagic Database Handler
 *
 * Handles connecting to and querying RootsMagic database files (.rmtree).
 * Supports RootsMagic versions 9 and 10.
 *
 * @package HeritagePress
 * @subpackage Importers
 */

namespace HeritagePress\Importers;

use PDO;
use PDOException;
use Exception;

class RootsMagicDatabase {
    /**
     * Database connection
     *
     * @var PDO
     */
    private $db = null;

    /**
     * RootsMagic version (9 or 10)
     *
     * @var int
     */
    private $version = null;

    /**
     * Database file path
     *
     * @var string
     */
    private $file_path = null;

    /**
     * Constructor
     *
     * @param string $file_path Path to the RootsMagic database file
     * @throws Exception If the file is not a valid RootsMagic database
     */    public function __construct($file_path) {
        if (!file_exists($file_path)) {
            throw new Exception("File not found: {$file_path}");
        }

        $this->file_path = $file_path;
        $this->connect();
        $this->detect_version();
        $this->connect();
        $this->detect_version();
    }

    /**
     * Connect to the SQLite database
     *
     * @throws Exception If unable to connect to the database
     */
    private function connect() {
        try {
            $this->db = new PDO("sqlite:{$this->file_path}");
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Failed to connect to RootsMagic database: " . $e->getMessage());
        }
    }

    /**
     * Detect RootsMagic version from database structure
     *
     * @throws Exception If version cannot be determined
     */
    private function detect_version() {
        try {
            // Check for RootsMagic 10 specific tables
            $rm10_check = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='DatabaseInformation'");
            if ($rm10_check && $rm10_check->fetch()) {
                $version_stmt = $this->db->query("SELECT Value FROM DatabaseInformation WHERE Name='Version'");
                if ($version_stmt) {
                    $version_info = $version_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($version_info && strpos($version_info['Value'], '10') === 0) {
                        $this->version = 10;
                        return;
                    }
                }
            }

            // Check for RootsMagic 9 specific tables
            $rm9_check = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='TreeInfo'");
            if ($rm9_check && $rm9_check->fetch()) {
                $version_stmt = $this->db->query("SELECT Version FROM TreeInfo LIMIT 1");
                if ($version_stmt) {
                    $version_info = $version_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($version_info && strpos($version_info['Version'], '9') === 0) {
                        $this->version = 9;
                        return;
                    }
                }
            }

            throw new Exception("Unable to determine RootsMagic version");
        } catch (PDOException $e) {
            throw new Exception("Error detecting RootsMagic version: " . $e->getMessage());
        }
    }

    /**
     * Get RootsMagic version
     *
     * @return int The detected version (9 or 10)
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get database connection
     *
     * @return PDO The database connection
     */
    public function get_connection() {
        return $this->db;
    }

    /**
     * Execute a query and return the results
     *
     * @param string $query The SQL query to execute
     * @param array $params Optional parameters for the query
     * @return array The query results
     * @throws Exception If the query fails
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Get list of all tables in the database
     *
     * @return array Table names
     */
    public function get_tables() {
        return $this->query("SELECT name FROM sqlite_master WHERE type='table'");
    }

    /**
     * Close database connection
     */
    public function __destruct() {
        $this->db = null;
    }
}
