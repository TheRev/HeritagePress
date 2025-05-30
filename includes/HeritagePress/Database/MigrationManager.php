<?php
namespace HeritagePress\Database;

class MigrationManager {
    private $wpdb;
    private $migrations_path;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->migrations_path = HERITAGE_PRESS_PLUGIN_DIR . 'includes/HeritagePress/Database/Migrations';
        $this->table_name = $wpdb->prefix . 'genealogy_migrations';
    }

    public function migrate() {
        // Create migrations table if it doesn't exist
        require_once $this->migrations_path . '/0000_create_migrations_table.php';
        \HeritagePress\Database\Migrations\CreateMigrationsTable::up();

        // Get all migration files
        $files = glob($this->migrations_path . '/*.php');
        natsort($files);

        // Get executed migrations
        $executed = $this->getExecutedMigrations();
        $batch = $this->getNextBatch();

        foreach ($files as $file) {
            $migration = basename($file, '.php');
            
            // Skip migrations that have already been executed
            if ($migration === '0000_create_migrations_table' || in_array($migration, $executed)) {
                continue;
            }

            // Include and execute the migration
            require_once $file;
            $class = '\\HeritagePress\\Database\\Migrations\\' . $this->getMigrationClassName($migration);
            
            if (class_exists($class)) {
                try {
                    $class::up();
                    $this->logMigration($migration, $batch);
                    error_log("Executed migration: $migration");
                } catch (\Exception $e) {
                    error_log("Migration failed: $migration - " . $e->getMessage());
                    throw $e;
                }
            }
        }
    }

    public function rollback() {
        $batch = $this->getLastBatch();
        if (!$batch) return;

        $migrations = $this->getMigrationsByBatch($batch);
        foreach (array_reverse($migrations) as $migration) {
            $file = $this->migrations_path . '/' . $migration . '.php';
            if (file_exists($file)) {
                require_once $file;
                $class = '\\HeritagePress\\Database\\Migrations\\' . $this->getMigrationClassName($migration);
                if (class_exists($class)) {
                    try {
                        $class::down();
                        $this->removeMigration($migration);
                        error_log("Rolled back migration: $migration");
                    } catch (\Exception $e) {
                        error_log("Rollback failed: $migration - " . $e->getMessage());
                        throw $e;
                    }
                }
            }
        }
    }

    private function getExecutedMigrations() {
        return $this->wpdb->get_col("SELECT migration FROM {$this->table_name}");
    }

    private function getNextBatch() {
        $last_batch = $this->wpdb->get_var("SELECT MAX(batch) FROM {$this->table_name}");
        return $last_batch ? $last_batch + 1 : 1;
    }

    private function getLastBatch() {
        return $this->wpdb->get_var("SELECT MAX(batch) FROM {$this->table_name}");
    }

    private function getMigrationsByBatch($batch) {
        return $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT migration FROM {$this->table_name} WHERE batch = %d ORDER BY id ASC",
            $batch
        ));
    }

    private function logMigration($migration, $batch) {
        $this->wpdb->insert($this->table_name, [
            'migration' => $migration,
            'batch' => $batch
        ]);
    }

    private function removeMigration($migration) {
        $this->wpdb->delete($this->table_name, ['migration' => $migration]);
    }

    private function getMigrationClassName($filename) {
        $parts = explode('_', $filename);
        array_shift($parts); // Remove the version number
        return str_replace(' ', '', ucwords(implode(' ', $parts)));
    }
}
