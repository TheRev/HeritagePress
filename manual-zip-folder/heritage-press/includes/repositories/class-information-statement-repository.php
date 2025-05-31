<?php
/**
 * Information Statement Repository Class
 *
 * Repository for managing Information Statements following Elizabeth Shown Mills' Evidence methodology.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Repositories;

use HeritagePress\Database\DatabaseManager; // Corrected: No underscore
use HeritagePress\Models\Information_Statement;
use HeritagePress\Core\Audit_Log_Observer;

class Information_Statement_Repository {

    private $wpdb;
    private $table_name;
    private $audit_observer;

    /**
     * Constructor
     */
    public function __construct(Audit_Log_Observer $audit_observer) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = DatabaseManager::get_table_prefix() . 'information_statements'; // Corrected: No underscore
        $this->audit_observer = $audit_observer;
    }

    /**
     * Converts a database row to an Information_Statement model
     */
    private function to_model(?\stdClass $db_row): ?Information_Statement {
        if (!$db_row) {
            return null;
        }
        return Information_Statement::from_db_object($db_row);
    }

    /**
     * Create a new information statement
     */
    public function create(array $data) {
        if (empty($data['source_id']) || empty($data['statement_text'])) {
            return false;
        }

        $defaults = [
            'uuid' => wp_generate_uuid4(),
            'statement_type' => 'SECONDARY',
            'information_quality' => 'UNKNOWN',
            'verification_status' => 'UNVERIFIED',
            'extracted_by_user_id' => get_current_user_id(),
            'extraction_date' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $data = array_merge($defaults, $data);

        // Get file_id from source if not provided
        if (empty($data['file_id'])) {
            $sources_table = DatabaseManager::get_table_prefix() . 'sources';
            $file_id = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT file_id FROM {$sources_table} WHERE id = %d",
                    $data['source_id']
                )
            );
            $data['file_id'] = $file_id ?: '';
        }

        $result = $this->wpdb->insert($this->table_name, $data);

        if ($result === false) {
            return false;
        }

        $statement_id = $this->wpdb->insert_id;
        $statement = $this->find_by_id($statement_id);

        // Log audit trail
        $this->audit_observer->log_action('create', 'information_statement', $statement_id, $data);

        return $statement;
    }

    /**
     * Find information statement by ID
     */
    public function find_by_id($id) {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );

        $row = $this->wpdb->get_row($query);
        return $this->to_model($row);
    }

    /**
     * Find information statement by UUID
     */
    public function find_by_uuid($uuid) {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE uuid = %s",
            $uuid
        );

        $row = $this->wpdb->get_row($query);
        return $this->to_model($row);
    }

    /**
     * Get information statements for a source
     */
    public function find_by_source($source_id, $filters = []) {
        $where_clause = "WHERE source_id = %d";
        $params = [$source_id];

        if (!empty($filters['statement_type'])) {
            $where_clause .= " AND statement_type = %s";
            $params[] = $filters['statement_type'];
        }

        if (!empty($filters['information_quality'])) {
            $where_clause .= " AND information_quality = %s";
            $params[] = $filters['information_quality'];
        }

        if (!empty($filters['verification_status'])) {
            $where_clause .= " AND verification_status = %s";
            $params[] = $filters['verification_status'];
        }

        if (!empty($filters['language'])) {
            $where_clause .= " AND language_original = %s";
            $params[] = $filters['language'];
        }

        $order_by = isset($filters['order_by']) ? $filters['order_by'] : 'extraction_date DESC';

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$order_by}",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get information statements for a citation
     */
    public function find_by_citation($citation_id) {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE citation_id = %d ORDER BY extraction_date DESC",
            $citation_id
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get information statements by file ID
     */
    public function find_by_file($file_id, $filters = []) {
        $where_clause = "WHERE file_id = %s";
        $params = [$file_id];

        if (!empty($filters['statement_type'])) {
            $where_clause .= " AND statement_type = %s";
            $params[] = $filters['statement_type'];
        }

        if (!empty($filters['extracted_by'])) {
            $where_clause .= " AND extracted_by_user_id = %d";
            $params[] = $filters['extracted_by'];
        }

        if (!empty($filters['unverified_only'])) {
            $where_clause .= " AND verification_status = 'UNVERIFIED'";
        }

        $order_by = isset($filters['order_by']) ? $filters['order_by'] : 'extraction_date DESC';

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$order_by}",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Search information statements by text content
     */
    public function search_by_content($search_term, $file_id = null, $limit = 50) {
        $where_clause = "WHERE (statement_text LIKE %s OR transcription_notes LIKE %s OR context_notes LIKE %s)";
        $search_param = '%' . $this->wpdb->esc_like($search_term) . '%';
        $params = [$search_param, $search_param, $search_param];

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY extraction_date DESC LIMIT %d",
            ...array_merge($params, [$limit])
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Update information statement
     */
    public function update($id, array $data) {
        $data['updated_at'] = current_time('mysql');

        $result = $this->wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            null,
            ['%d']
        );

        if ($result !== false) {
            $this->audit_observer->log_action('update', 'information_statement', $id, $data);
            return $this->find_by_id($id);
        }

        return false;
    }

    /**
     * Mark statement as verified
     */
    public function mark_verified($id, $verification_notes = null) {
        $update_data = [
            'verification_status' => 'VERIFIED',
            'updated_at' => current_time('mysql')
        ];

        if ($verification_notes) {
            $current_notes = $this->wpdb->get_var(
                $this->wpdb->prepare("SELECT transcription_notes FROM {$this->table_name} WHERE id = %d", $id)
            );
            $update_data['transcription_notes'] = $current_notes . "\n\nVerification: " . $verification_notes;
        }

        return $this->update($id, $update_data);
    }

    /**
     * Mark statement as questioned
     */
    public function mark_questioned($id, $reason = null) {
        $update_data = [
            'verification_status' => 'QUESTIONED',
            'updated_at' => current_time('mysql')
        ];

        if ($reason) {
            $current_notes = $this->wpdb->get_var(
                $this->wpdb->prepare("SELECT transcription_notes FROM {$this->table_name} WHERE id = %d", $id)
            );
            $update_data['transcription_notes'] = $current_notes . "\n\nQuestioned: " . $reason;
        }

        return $this->update($id, $update_data);
    }

    /**
     * Get statements needing verification
     */
    public function get_unverified_statements($file_id = null, $older_than_days = 30) {
        $where_clause = "WHERE verification_status = 'UNVERIFIED' AND DATEDIFF(NOW(), extraction_date) > %d";
        $params = [$older_than_days];

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY extraction_date ASC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get statements by quality level
     */
    public function get_by_quality_level($quality_level, $file_id = null) {
        $where_clause = "WHERE information_quality = %s";
        $params = [$quality_level];

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY extraction_date DESC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get statements that have evidence analyses
     */
    public function get_with_evidence_analyses($file_id = null) {        $where_clause = "WHERE EXISTS (
            SELECT 1 FROM " . DatabaseManager::get_table_prefix() . "evidence_analysis ea 
            WHERE ea.information_statement_id = ist.id
        )";
        $params = [];

        if ($file_id) {
            $where_clause .= " AND ist.file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT ist.* FROM {$this->table_name} ist {$where_clause} ORDER BY ist.extraction_date DESC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get statements without evidence analyses
     */
    public function get_without_evidence_analyses($file_id = null) {        $where_clause = "WHERE NOT EXISTS (
            SELECT 1 FROM " . DatabaseManager::get_table_prefix() . "evidence_analysis ea 
            WHERE ea.information_statement_id = ist.id
        )";
        $params = [];

        if ($file_id) {
            $where_clause .= " AND ist.file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT ist.* FROM {$this->table_name} ist {$where_clause} ORDER BY ist.extraction_date DESC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Delete information statement
     */
    public function delete($id) {        // First check if there are related evidence analyses
        $evidence_table = DatabaseManager::get_table_prefix() . 'evidence_analysis';
        $evidence_count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$evidence_table} WHERE information_statement_id = %d",
                $id
            )
        );

        if ($evidence_count > 0) {
            // Don't delete if there are related evidence analyses
            return false;
        }

        // Log the deletion
        $statement = $this->find_by_id($id);
        if ($statement) {
            $this->audit_observer->log_action('delete', 'information_statement', $id, []);
        }

        $result = $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get extraction statistics
     */
    public function get_extraction_statistics($file_id = null) {
        $where_clause = $file_id ? "WHERE file_id = %s" : "";
        $params = $file_id ? [$file_id] : [];

        $base_query = "FROM {$this->table_name} {$where_clause}";

        $stats = [];

        // Total count
        $stats['total_statements'] = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) {$base_query}", ...$params)
        );

        // By statement type
        $stats['by_statement_type'] = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT statement_type, COUNT(*) as count {$base_query} GROUP BY statement_type",
                ...$params
            )
        );

        // By information quality
        $stats['by_information_quality'] = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT information_quality, COUNT(*) as count {$base_query} GROUP BY information_quality",
                ...$params
            )
        );

        // By verification status
        $stats['by_verification_status'] = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT verification_status, COUNT(*) as count {$base_query} GROUP BY verification_status",
                ...$params
            )
        );

        // Extraction activity by user
        $stats['by_extractor'] = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT extracted_by_user_id, COUNT(*) as count {$base_query} GROUP BY extracted_by_user_id",
                ...$params
            )
        );

        return $stats;
    }

    /**
     * Bulk update verification status
     */
    public function bulk_verify($statement_ids, $verification_status = 'VERIFIED') {
        if (empty($statement_ids) || !is_array($statement_ids)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($statement_ids), '%d'));
        $params = array_merge([$verification_status, current_time('mysql')], $statement_ids);

        $query = $this->wpdb->prepare(
            "UPDATE {$this->table_name} 
            SET verification_status = %s, updated_at = %s 
            WHERE id IN ({$placeholders})",
            ...$params
        );

        $result = $this->wpdb->query($query);

        if ($result !== false) {
            foreach ($statement_ids as $id) {
                $this->audit_observer->log_action('bulk_verify', 'information_statement', $id, [
                    'verification_status' => $verification_status
                ]);
            }
        }

        return $result !== false;
    }

    /**
     * Get reliability score distribution
     */
    public function get_reliability_distribution($file_id = null) {
        $statements = $this->find_by_file($file_id);
        $distribution = [
            'high' => 0,
            'medium' => 0,
            'low' => 0
        ];

        foreach ($statements as $statement) {
            $score = $statement->getReliabilityScore();
            if ($score >= 7) {
                $distribution['high']++;
            } elseif ($score >= 4) {
                $distribution['medium']++;
            } else {
                $distribution['low']++;
            }
        }

        return $distribution;
    }
}
