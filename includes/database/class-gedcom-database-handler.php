<?php
namespace HeritagePress\Database;

class GedcomDatabaseHandler {
    private $db_manager;

    public function __construct() {
        $this->db_manager = new DatabaseManager();
    }

    /**
     * Store GEDCOM data in database
     * 
     * @param array $data GEDCOM data to store
     * @param string $file_id Optional GEDCOM file identifier
     * @return bool True on success, false on failure
     */    public function storeGedcomData($data, $file_id = null) {
        global $wpdb;
        
        // Generate file ID if not provided
        if (!$file_id) {
            $file_id = wp_generate_uuid4();
        }
        
        // Create relationship handler
        $relationship_handler = new \HeritagePress\GEDCOM\GedcomFamilyRelationshipHandler($file_id);
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            $individual_ids_map = []; // Map GEDCOM ID to database ID
            $family_ids_map = []; // Map GEDCOM ID to database ID
        
            // Store individuals
            if (!empty($data['individuals'])) {
                foreach ($data['individuals'] as $individual) {
                    $db_id = $this->storeIndividual($individual, $file_id);
                    if ($db_id) {
                        $individual_ids_map[$individual['id']] = $db_id;
                        // Process this individual for family relationships
                        $relationship_handler->processIndividual($individual['data'], $individual['id'], $db_id);
                    }
                }
            }

            // Store families
            if (!empty($data['families'])) {
                foreach ($data['families'] as $family) {
                    $db_id = $this->storeFamily($family, $file_id);
                    if ($db_id) {
                        $family_ids_map[$family['id']] = $db_id;
                        // Store the family ID for relationship creation
                        $relationship_handler->processFamily($family['id'], $db_id);
                    }
                }
            }

            // Store sources
            if (!empty($data['sources'])) {
                foreach ($data['sources'] as $source) {
                    $this->storeSource($source, $file_id);
                }
            }
            
            // Create family relationships after individuals and families have been stored
            $relationship_stats = $relationship_handler->createRelationships();
            error_log('Created family relationships: ' . print_r($relationship_stats, true));

            // Store media
            if (!empty($data['media'])) {
                foreach ($data['media'] as $media) {
                    $this->storeMedia($media, $file_id);
                }
            }

            // Store places
            if (!empty($data['places'])) {
                foreach ($data['places'] as $place) {
                    $this->storePlace($place, $file_id);
                }
            }

            $wpdb->query('COMMIT');
            return $file_id;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Store individual record
     */
    private function storeIndividual($individual, $file_id) {
        global $wpdb;
        
        // Extract basic data
        $data = [
            'gedcom_id' => $individual['id'],
            'first_name' => '',
            'last_name' => '',
            'sex' => '',
            'birth_date' => '',
            'death_date' => '',
            'data' => json_encode($individual),
            'file_id' => $file_id
        ];

        // Extract name
        foreach ($individual['data'] as $item) {
            if ($item['tag'] === 'NAME') {
                list($first, $last) = $this->parseName($item['value']);
                $data['first_name'] = $first;
                $data['last_name'] = $last;
            } elseif ($item['tag'] === 'SEX') {
                $data['sex'] = $item['value'];
            } elseif ($item['tag'] === 'BIRT' && !empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    if ($child['tag'] === 'DATE') {
                        $data['birth_date'] = $child['value'];
                    }
                }
            } elseif ($item['tag'] === 'DEAT' && !empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    if ($child['tag'] === 'DATE') {
                        $data['death_date'] = $child['value'];
                    }
                }
            }
        }

        $wpdb->insert(
            $wpdb->prefix . 'genealogy_individuals',
            $data
        );

        return $wpdb->insert_id;
    }

    /**
     * Store family record
     */
    private function storeFamily($family, $file_id) {
        global $wpdb;
        
        $data = [
            'gedcom_id' => $family['id'],
            'husband_id' => '',
            'wife_id' => '',
            'marriage_date' => '',
            'data' => json_encode($family),
            'file_id' => $file_id
        ];

        foreach ($family['data'] as $item) {
            if ($item['tag'] === 'HUSB') {
                $data['husband_id'] = $item['value'];
            } elseif ($item['tag'] === 'WIFE') {
                $data['wife_id'] = $item['value'];
            } elseif ($item['tag'] === 'MARR' && !empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    if ($child['tag'] === 'DATE') {
                        $data['marriage_date'] = $child['value'];
                    }
                }
            }
        }

        $wpdb->insert(
            $wpdb->prefix . 'genealogy_families',
            $data
        );

        return $wpdb->insert_id;
    }

    /**
     * Store source record
     */
    private function storeSource($source, $file_id) {
        global $wpdb;
        
        $data = [
            'gedcom_id' => $source['id'],
            'title' => '',
            'author' => '',
            'data' => json_encode($source),
            'file_id' => $file_id
        ];

        foreach ($source['data'] as $item) {
            if ($item['tag'] === 'TITL') {
                $data['title'] = $item['value'];
            } elseif ($item['tag'] === 'AUTH') {
                $data['author'] = $item['value'];
            }
        }

        $wpdb->insert(
            $wpdb->prefix . 'genealogy_sources',
            $data
        );

        return $wpdb->insert_id;
    }

    /**
     * Store media record
     */
    private function storeMedia($media, $file_id) {
        global $wpdb;
        
        $data = [
            'gedcom_id' => $media['id'],
            'title' => $media['title'] ?? '',
            'file_path' => '',
            'data' => json_encode($media),
            'file_id' => $file_id
        ];

        if (!empty($media['files'][0]['path'])) {
            $data['file_path'] = $media['files'][0]['path'];
        }

        $wpdb->insert(
            $wpdb->prefix . 'genealogy_media',
            $data
        );

        return $wpdb->insert_id;
    }

    /**
     * Store place record
     */
    private function storePlace($place, $file_id) {
        global $wpdb;
        
        $data = [
            'name' => $place['original'],
            'standardized_name' => $place['standardized'] ?? '',
            'latitude' => $place['coordinates']['latitude'] ?? null,
            'longitude' => $place['coordinates']['longitude'] ?? null,
            'data' => json_encode($place),
            'file_id' => $file_id
        ];

        $wpdb->insert(
            $wpdb->prefix . 'genealogy_places',
            $data
        );

        return $wpdb->insert_id;
    }

    /**
     * Parse GEDCOM name into first and last names
     */
    private function parseName($name) {
        // Remove slashes from surname
        $name = trim($name);
        preg_match('/(.+?)\s*\/(.+?)\/?/', $name, $matches);
        
        if (!empty($matches)) {
            return [
                trim($matches[1]),
                trim($matches[2])
            ];
        }
        
        // If no surname markers, assume last word is surname
        $parts = explode(' ', $name);
        if (count($parts) > 1) {
            $surname = array_pop($parts);
            return [
                implode(' ', $parts),
                $surname
            ];
        }
        
        return [$name, ''];
    }

    /**
     * Delete all GEDCOM data from database
     * 
     * @return bool True on success, false on failure
     */
    public function deleteAllGedcomData() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'genealogy_';
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Delete from all tables
            $tables = ['individuals', 'families', 'events', 'media', 'places', 'sources'];
            
            foreach ($tables as $table) {
                $wpdb->query("DELETE FROM {$prefix}{$table}");
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Delete specific GEDCOM data by file ID
     * 
     * @param string $file_id The GEDCOM file ID to delete
     * @return bool True on success, false on failure
     */
    public function deleteGedcomDataByFileId($file_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'genealogy_';
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Delete records from each table where they match the file_id
            $tables = ['individuals', 'families', 'events', 'media', 'places', 'sources'];
            
            foreach ($tables as $table) {
                $wpdb->delete(
                    "{$prefix}{$table}",
                    ['file_id' => $file_id],
                    ['%s']
                );
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Delete specific GEDCOM records by type and IDs
     * 
     * @param string $type Record type (individuals, families, events, etc.)
     * @param array $ids Array of record IDs to delete
     * @return bool True on success, false on failure
     */
    public function deleteGedcomRecords($type, $ids) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'genealogy_';
        
        if (!is_array($ids) || empty($ids)) {
            throw new \InvalidArgumentException('IDs must be a non-empty array');
        }

        // Validate table type
        $valid_types = ['individuals', 'families', 'events', 'media', 'places', 'sources'];
        if (!in_array($type, $valid_types)) {
            throw new \InvalidArgumentException('Invalid record type');
        }

        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Create placeholders for the IN clause
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            
            $sql = $wpdb->prepare(
                "DELETE FROM {$prefix}{$type} WHERE id IN ($placeholders)",
                $ids
            );
            
            $wpdb->query($sql);

            // If deleting individuals or families, also delete related events
            if ($type === 'individuals' || $type === 'families') {
                $id_field = $type === 'individuals' ? 'individual_id' : 'family_id';
                $sql = $wpdb->prepare(
                    "DELETE FROM {$prefix}events WHERE {$id_field} IN ($placeholders)",
                    $ids
                );
                $wpdb->query($sql);
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Store or update a GEDCOM tree
     * 
     * @param array $data GEDCOM data to store
     * @param string $file_name Original filename
     * @param string $tree_id Optional tree ID for updates
     * @param array $meta Optional metadata
     * @return string Tree ID
     */
    public function storeGedcomTree($data, $file_name, $tree_id = null, $meta = []) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'genealogy_';
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            if ($tree_id) {
                // This is an update to an existing tree
                $existing_tree = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$prefix}gedcom_trees WHERE tree_id = %s",
                        $tree_id
                    )
                );

                if (!$existing_tree) {
                    throw new \Exception('Tree not found');
                }

                // Archive current version
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$prefix}individuals SET status = 'archived' WHERE file_id = %s",
                        $tree_id
                    )
                );
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$prefix}families SET status = 'archived' WHERE file_id = %s",
                        $tree_id
                    )
                );
                // ... (similar for other tables)

                // Increment version
                $version = $existing_tree->version + 1;
            } else {
                // New tree
                $tree_id = wp_generate_uuid4();
                $version = 1;
            }

            // Update tree metadata
            $tree_data = [
                'tree_id' => $tree_id,
                'file_name' => $file_name,
                'version' => $version,
                'meta' => json_encode($meta)
            ];

            if ($existing_tree) {
                $wpdb->update("{$prefix}gedcom_trees", $tree_data, ['tree_id' => $tree_id]);
            } else {
                $wpdb->insert("{$prefix}gedcom_trees", $tree_data);
            }

            // Store GEDCOM data with the tree ID
            $this->storeGedcomData($data, $tree_id);

            $wpdb->query('COMMIT');
            return $tree_id;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Get list of GEDCOM trees
     * 
     * @param string $status Filter by status (active|archived|all)
     * @return array List of trees
     */
    public function getGedcomTrees($status = 'active') {
        global $wpdb;
        $prefix = $wpdb->prefix . 'genealogy_';

        $where = '';
        if ($status !== 'all') {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }

        return $wpdb->get_results(
            "SELECT * FROM {$prefix}gedcom_trees {$where} ORDER BY upload_date DESC"
        );
    }

    /**
     * Archive a GEDCOM tree
     * 
     * @param string $tree_id Tree ID to archive
     * @return bool Success
     */
    public function archiveGedcomTree($tree_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'genealogy_';

        return $wpdb->update(
            "{$prefix}gedcom_trees",
            ['status' => 'archived'],
            ['tree_id' => $tree_id]
        );
    }

    /**
     * Delete a GEDCOM tree and all its data
     * 
     * @param string $tree_id Tree ID to delete
     * @param bool $force If true, permanently delete; if false, just archive
     * @return bool Success
     */
    public function deleteGedcomTree($tree_id, $force = false) {
        if (!$force) {
            return $this->archiveGedcomTree($tree_id);
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'genealogy_';
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Delete from gedcom_trees
            $wpdb->delete("{$prefix}gedcom_trees", ['tree_id' => $tree_id]);

            // Delete all related data
            $tables = ['individuals', 'families', 'events', 'media', 'places', 'sources'];
            foreach ($tables as $table) {
                $wpdb->delete("{$prefix}{$table}", ['file_id' => $tree_id]);
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Get tree information including statistics
     * 
     * @param string $tree_id Tree ID
     * @return array|null Tree information
     */
    public function getTreeInfo($tree_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'genealogy_';

        $tree = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$prefix}gedcom_trees WHERE tree_id = %s",
                $tree_id
            )
        );

        if (!$tree) {
            return null;
        }

        // Get statistics
        $stats = [];
        $tables = ['individuals', 'families', 'events', 'media', 'places', 'sources'];
        foreach ($tables as $table) {
            $stats[$table] = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$prefix}{$table} WHERE file_id = %s AND status = 'active'",
                    $tree_id
                )
            );
        }

        return [
            'tree' => $tree,
            'statistics' => $stats
        ];
    }
}
