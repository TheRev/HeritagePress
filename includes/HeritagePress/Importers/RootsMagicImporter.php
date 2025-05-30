<?php
namespace HeritagePress\Importers;

use PDO;
use Exception;
use HeritagePress\Models\Individual;
use HeritagePress\Models\Family;
use HeritagePress\Models\Event;
use HeritagePress\Models\Place;
use HeritagePress\Models\Source;
use HeritagePress\Models\Citation;
use HeritagePress\Database\DatabaseManager;

/**
 * RootsMagic Database Importer
 * 
 * Handles importing genealogy data from RootsMagic database files (.rmtree)
 * Supports RootsMagic 9 and 10 formats.
 */
class RootsMagicImporter implements HeritageImporter {
    private $errors = [];
    private $warnings = [];
    private $debug_messages = [];

    /**
     * Check if the given file can be imported by this importer
     *
     * @param string $file_path Path to the file to check
     * @return bool True if file can be imported
     */
    public function can_import($file_path) {
        echo "[[DEBUG V9: RootsMagicImporter::can_import called with: $file_path]]\n";
        
        // Reset error arrays
        $this->errors = [];
        $this->warnings = [];
        $this->debug_messages = [];
        
        // Check if file exists
        if (!file_exists($file_path)) {
            $this->errors[] = "File does not exist: $file_path";
            echo "[[DEBUG V9: File does not exist: $file_path]]\n";
            return false;
        }
        
        // Check if file is readable
        if (!is_readable($file_path)) {
            $this->errors[] = "File is not readable: $file_path";
            echo "[[DEBUG V9: File is not readable: $file_path]]\n";
            return false;
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        echo "[[DEBUG V9: File extension: '$extension']]\n";
        
        if ($extension !== 'rmtree') {
            $this->errors[] = "File does not have .rmtree extension";
            echo "[[DEBUG V9: Wrong extension - expected 'rmtree', got '$extension']]\n";
            return false;
        }
        
        // Read SQLite header (first 16 bytes should be "SQLite format 3\0")
        $header = @file_get_contents($file_path, false, null, 0, 16);
        if ($header === false) {
            $this->errors[] = "Could not read file header";
            echo "[[DEBUG V9: Could not read file header from: $file_path]]\n";
            return false;
        }
        
        echo "[[DEBUG V9: Header length: " . strlen($header) . "]]\n";
        echo "[[DEBUG V9: Header hex: " . bin2hex($header) . "]]\n";
        echo "[[DEBUG V9: Header text: '" . addcslashes($header, "\0..\31\177..\377") . "']]\n";
        
        $expected_header = "SQLite format 3\0";
        echo "[[DEBUG V9: Expected header: '" . addcslashes($expected_header, "\0..\31\177..\377") . "']]\n";
        echo "[[DEBUG V9: Expected header hex: " . bin2hex($expected_header) . "]]\n";
        
        // Check SQLite header
        if (substr($header, 0, 15) !== "SQLite format 3") {
            $this->errors[] = "File is not a valid SQLite database (missing SQLite header)";
            echo "[[DEBUG V9: SQLite header check failed]]\n";
            echo "[[DEBUG V9: Got: '" . substr($header, 0, 15) . "']]\n";
            echo "[[DEBUG V9: Expected: 'SQLite format 3']]\n";
            return false;
        }
        
        echo "[[DEBUG V9: SQLite header check passed]]\n";
        
        // Check if PDO SQLite is available
        if (!extension_loaded('pdo_sqlite')) {
            $this->errors[] = "PDO SQLite extension is not available";
            echo "[[DEBUG V9: PDO SQLite extension not loaded]]\n";
            return false;
        }
        
        echo "[[DEBUG V9: PDO SQLite extension is available]]\n";
        
        // Try to open the database and check for RootsMagic tables
        try {
            echo "[[DEBUG V9: Attempting to open database with PDO]]\n";
            $pdo = new \PDO("sqlite:$file_path", null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 10
            ]);
              echo "[[DEBUG V9: Database opened successfully]]\n";
            
            // Check for RootsMagic-specific tables
            // First try RMTREE_METADATA (newer versions), then fall back to other characteristic tables
            $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='RMTREE_METADATA'");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result) {
                echo "[[DEBUG V9: RMTREE_METADATA table found - this is a RootsMagic database]]\n";
                
                // Get RootsMagic version info
                try {
                    $stmt = $pdo->prepare("SELECT Value FROM RMTREE_METADATA WHERE Key='VERSION'");
                    $stmt->execute();
                    $version = $stmt->fetchColumn();
                    echo "[[DEBUG V9: RootsMagic version: " . ($version ?: 'unknown') . "]]\n";
                } catch (\Exception $e) {
                    echo "[[DEBUG V9: Could not get version info: " . $e->getMessage() . "]]\n";
                }
                
                $pdo = null;
                echo "[[DEBUG V9: Database validation completed successfully]]\n";
                return true;
            }
            
            echo "[[DEBUG V9: RMTREE_METADATA table not found, checking for other RootsMagic tables]]\n";
            
            // Check for characteristic RootsMagic tables
            $requiredTables = ['PersonTable', 'FamilyTable', 'EventTable'];
            $foundTables = [];
            
            foreach ($requiredTables as $tableName) {
                $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
                $stmt->execute([$tableName]);
                if ($stmt->fetch()) {
                    $foundTables[] = $tableName;
                    echo "[[DEBUG V9: Found required table: $tableName]]\n";
                } else {
                    echo "[[DEBUG V9: Missing required table: $tableName]]\n";
                }
            }
            
            if (count($foundTables) === count($requiredTables)) {
                echo "[[DEBUG V9: All required RootsMagic tables found - this appears to be a RootsMagic database]]\n";
                  // Try to get version from ConfigTable if available
                try {
                    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='ConfigTable'");
                    $stmt->execute();
                    if ($stmt->fetch()) {
                        echo "[[DEBUG V9: ConfigTable found, attempting to get version info]]\n";
                        $stmt = $pdo->prepare("SELECT DataRec FROM ConfigTable WHERE RecType=1 LIMIT 1");
                        $stmt->execute();
                        $dataRec = $stmt->fetchColumn();
                        if ($dataRec) {
                            // Parse XML to extract version
                            $xml = simplexml_load_string($dataRec);
                            if ($xml && isset($xml->Version)) {
                                $version = (string)$xml->Version;
                                echo "[[DEBUG V9: RootsMagic version from ConfigTable: $version]]\n";
                            } else {
                                echo "[[DEBUG V9: Could not parse version from ConfigTable XML]]\n";
                            }
                        } else {
                            echo "[[DEBUG V9: No configuration data found in ConfigTable]]\n";
                        }
                    }
                } catch (\Exception $e) {
                    echo "[[DEBUG V9: Could not get version from ConfigTable: " . $e->getMessage() . "]]\n";
                }
                
                $pdo = null;
                echo "[[DEBUG V9: Database validation completed successfully]]\n";
                return true;
            }
            
            $this->errors[] = "Database does not contain required RootsMagic tables";
            echo "[[DEBUG V9: Not enough RootsMagic tables found]]\n";
            echo "[[DEBUG V9: Required: " . implode(', ', $requiredTables) . "]]\n";
            echo "[[DEBUG V9: Found: " . implode(', ', $foundTables) . "]]\n";
            
            // List all tables for debugging
            $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
            $stmt->execute();
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            echo "[[DEBUG V9: Available tables: " . implode(', ', $tables) . "]]\n";
            
            $pdo = null;
            return false;
            
        } catch (\Exception $e) {
            $this->errors[] = "Error opening database: " . $e->getMessage();
            echo "[[DEBUG V9: Database error: " . $e->getMessage() . "]]\n";
            return false;
        }
    }

    /**
     * Get the name of the format this importer handles
     *
     * @return string Format name
     */
    public function get_format_name() {
        return 'RootsMagic';
    }

    /**
     * Get supported file extensions for this importer
     *
     * @return array Array of supported extensions (without dots)
     */
    public function get_supported_extensions() {
        return ['rmtree'];
    }

    /**
     * Validate the file and return detailed validation results
     *
     * @param string $file_path Path to the file to validate
     * @return array Validation results with 'valid', 'errors', 'warnings', 'version'
     */
    public function validate($file_path) {
        // Reset error arrays
        $this->errors = [];
        $this->warnings = [];
        $this->debug_messages = [];
        
        $can_import = $this->can_import($file_path);
        
        $version = null;
        if ($can_import) {
            // Try to get version information
            try {
                $pdo = new \PDO("sqlite:$file_path", null, null, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_TIMEOUT => 10
                ]);
                
                $stmt = $pdo->prepare("SELECT Value FROM RMTREE_METADATA WHERE Key='VERSION'");
                $stmt->execute();
                $version = $stmt->fetchColumn();
                
                $pdo = null;
            } catch (\Exception $e) {
                $this->warnings[] = "Could not determine RootsMagic version: " . $e->getMessage();
            }
        }
        
        return [
            'valid' => $can_import,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'version' => $version ?: 'unknown'
        ];
    }

    /**
     * Import genealogy data from the file
     *
     * @param string $file_path Path to the file to import
     * @param array $options Import options
     * @return array Import results with 'message' and 'stats'
     * @throws \Exception If import fails
     */
    public function import($file_path, $options = []) {
        // Reset error arrays
        $this->errors = [];
        $this->warnings = [];
        $this->debug_messages = [];
        
        if (!$this->can_import($file_path)) {
            throw new \Exception("Cannot import file: " . implode("; ", $this->errors));
        }
        
        // TODO: Implement actual import logic
        // This is a placeholder implementation
        
        try {
            $pdo = new \PDO("sqlite:$file_path", null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 30
            ]);
            
            // Get basic statistics
            $stats = [];
            
            // Count individuals
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM PersonTable");
                $stmt->execute();
                $stats['individuals'] = $stmt->fetchColumn();
            } catch (\Exception $e) {
                $stats['individuals'] = 0;
                $this->warnings[] = "Could not count individuals: " . $e->getMessage();
            }
            
            // Count families
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM FamilyTable");
                $stmt->execute();
                $stats['families'] = $stmt->fetchColumn();
            } catch (\Exception $e) {
                $stats['families'] = 0;
                $this->warnings[] = "Could not count families: " . $e->getMessage();
            }
            
            $pdo = null;
            
            return [
                'message' => 'RootsMagic file validation completed successfully. Full import not yet implemented.',
                'stats' => $stats
            ];
            
        } catch (\Exception $e) {
            throw new \Exception("Import failed: " . $e->getMessage());
        }
    }

    /**
     * Get any errors that occurred during the last operation
     *
     * @return array Array of error messages
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get any warnings that occurred during the last operation
     *
     * @return array Array of warning messages
     */
    public function get_warnings() {
        return $this->warnings;
    }

    /**
     * Get debug messages from the last operation
     *
     * @return array Array of debug messages
     */
    public function get_debug_messages() {
        return $this->debug_messages;
    }
}
