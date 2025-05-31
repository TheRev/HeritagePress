<?php
/**
 * Evidence Analysis Repository Class
 *
 * Repository for managing Evidence Analysis following Elizabeth Shown Mills' Evidence methodology.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Repositories;

// Load WordPress compatibility if not in WordPress context  
if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../wordpress-compatibility.php';
}

use HeritagePress\Database\DatabaseManager;
use HeritagePress\Models\Evidence_Analysis;
use HeritagePress\Core\Audit_Log_Observer;

class Evidence_Analysis_Repository {

    /** @var wpdb */
    private $wpdb;
    private $table_name;
    private $audit_observer;

    /**
     * Constructor
     */
    public function __construct(Audit_Log_Observer $audit_observer) {        
        global $wpdb;
        /** @var wpdb $wpdb */
        $this->wpdb = $wpdb;
        $this->table_name = DatabaseManager::get_table_prefix() . 'evidence_analysis';
        $this->audit_observer = $audit_observer;
    }

    /**
     * Converts a database row to an Evidence_Analysis model
     */
    private function to_model(?\stdClass $db_row): ?Evidence_Analysis {
        if (!$db_row) {
            return null;
        }
        return Evidence_Analysis::from_db_object($db_row);
    }

    /**
     * Create a new evidence analysis
     */
    public function create(array $data) {
        if (empty($data['information_statement_id']) || empty($data['evidence_type'])) {
            return false;
        }

        $defaults = [
            'uuid' => wp_generate_uuid4(),
            'file_id' => '', // Will be inherited from information statement
            'relevance_score' => 5,
            'confidence_level' => 'MEDIUM',
            'evidence_weight' => 'MODERATE',
            'analyst_user_id' => get_current_user_id(),
            'analysis_date' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $data = array_merge($defaults, $data);

        // Get file_id from information statement if not provided
        if (empty($data['file_id'])) {
            $info_table = DatabaseManager::get_table_prefix() . 'information_statements';
            $file_id = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT file_id FROM {$info_table} WHERE id = %d",
                    $data['information_statement_id']
                )
            );
            $data['file_id'] = $file_id ?: '';
        }

        $result = $this->wpdb->insert($this->table_name, $data);

        if ($result === false) {
            return false;
        }

        $analysis_id = $this->wpdb->insert_id;
        $analysis = $this->find_by_id($analysis_id);

        // Auto-assess confidence level
        if ($analysis) {
            $analysis->autoAssessConfidence();
        }

        // Log audit trail
        $this->audit_observer->log_action('create', 'evidence_analysis', $analysis_id, $data);

        return $analysis;
    }

    /**
     * Find evidence analysis by ID
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
     * Find evidence analysis by UUID
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
     * Get evidence analyses for a research question
     */
    public function find_by_research_question($research_question_id, $filters = []) {
        $where_clause = "WHERE research_question_id = %d";
        $params = [$research_question_id];

        if (!empty($filters['evidence_type'])) {
            $where_clause .= " AND evidence_type = %s";
            $params[] = $filters['evidence_type'];
        }

        if (!empty($filters['confidence_level'])) {
            $where_clause .= " AND confidence_level = %s";
            $params[] = $filters['confidence_level'];
        }

        if (!empty($filters['evidence_weight'])) {
            $where_clause .= " AND evidence_weight = %s";
            $params[] = $filters['evidence_weight'];
        }

        if (!empty($filters['min_relevance'])) {
            $where_clause .= " AND relevance_score >= %d";
            $params[] = $filters['min_relevance'];
        }

        $order_by = isset($filters['order_by']) ? $filters['order_by'] : 'relevance_score DESC, analysis_date DESC';

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$order_by}",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get evidence analyses by information statement
     */
    public function find_by_information_statement($info_statement_id) {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE information_statement_id = %d ORDER BY analysis_date DESC",
            $info_statement_id
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get evidence analyses by file ID
     */
    public function find_by_file($file_id, $filters = []) {
        $where_clause = "WHERE file_id = %s";
        $params = [$file_id];

        if (!empty($filters['evidence_type'])) {
            $where_clause .= " AND evidence_type = %s";
            $params[] = $filters['evidence_type'];
        }

        if (!empty($filters['analyst_user_id'])) {
            $where_clause .= " AND analyst_user_id = %d";
            $params[] = $filters['analyst_user_id'];
        }

        $order_by = isset($filters['order_by']) ? $filters['order_by'] : 'analysis_date DESC';

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$order_by}",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Update evidence analysis
     */    public function update($id, array $data) {
        $data['updated_at'] = current_time('mysql');

        /** @var wpdb $wpdb_instance */
        $wpdb_instance = $this->wpdb;
        $result = $wpdb_instance->update(
            $this->table_name,
            $data,
            ['id' => $id],
            null,
            ['%d']
        );

        if ($result !== false) {
            $this->audit_observer->log_action('update', 'evidence_analysis', $id, $data);
            $analysis = $this->find_by_id($id);
            
            // Re-assess confidence if interpretation changed
            if (isset($data['interpretation_notes']) && $analysis) {
                $analysis->autoAssessConfidence();
            }
            
            return $analysis;
        }

        return false;
    }

    /**
     * Find conflicting evidence
     */
    public function find_conflicts($research_question_id) {
        $analyses = $this->find_by_research_question($research_question_id, [
            'evidence_type' => 'DIRECT'
        ]);

        $conflicts = [];
        foreach ($analyses as $analysis) {
            $analysis_conflicts = $analysis->findConflicts();
            if (!empty($analysis_conflicts)) {
                $conflicts = array_merge($conflicts, $analysis_conflicts);
            }
        }

        return $conflicts;
    }

    /**
     * Get evidence strength distribution
     */
    public function get_strength_distribution($research_question_id = null, $file_id = null) {
        $where_clause = "WHERE 1=1";
        $params = [];

        if ($research_question_id) {
            $where_clause .= " AND research_question_id = %d";
            $params[] = $research_question_id;
        }

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT 
                evidence_weight,
                confidence_level,
                evidence_type,
                COUNT(*) as count,
                AVG(relevance_score) as avg_relevance
            FROM {$this->table_name} 
            {$where_clause}
            GROUP BY evidence_weight, confidence_level, evidence_type
            ORDER BY evidence_weight, confidence_level",
            ...$params
        );

        return $this->wpdb->get_results($query);
    }

    /**
     * Get analyses needing review
     */
    public function get_analyses_needing_review($file_id = null) {
        $where_clause = "WHERE (
            confidence_level = 'UNCERTAIN' OR
            relevance_score < 3 OR
            (interpretation_notes IS NULL OR interpretation_notes = '') OR
            DATEDIFF(NOW(), analysis_date) > 90
        )";
        $params = [];

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY analysis_date ASC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get top evidence by strength
     */
    public function get_strongest_evidence($research_question_id, $limit = 10) {
        $query = $this->wpdb->prepare(
            "SELECT ea.*, 
                CASE 
                    WHEN ea.evidence_weight = 'STRONG' THEN 4
                    WHEN ea.evidence_weight = 'MODERATE' THEN 3
                    WHEN ea.evidence_weight = 'WEAK' THEN 2
                    ELSE 1
                END as weight_score,
                CASE 
                    WHEN ea.confidence_level = 'HIGH' THEN 4
                    WHEN ea.confidence_level = 'MEDIUM' THEN 3
                    WHEN ea.confidence_level = 'LOW' THEN 2
                    ELSE 1
                END as confidence_score
            FROM {$this->table_name} ea
            WHERE ea.research_question_id = %d
            ORDER BY (weight_score * confidence_score * relevance_score) DESC
            LIMIT %d",
            $research_question_id,
            $limit
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Delete evidence analysis
     */
    public function delete($id) {
        // First log the deletion
        $analysis = $this->find_by_id($id);
        if ($analysis) {
            $this->audit_observer->log_action('delete', 'evidence_analysis', $id, []);
        }        /** @var wpdb $wpdb_instance */
        $wpdb_instance = $this->wpdb;
        $result = $wpdb_instance->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Batch update confidence levels
     */
    public function batch_reassess_confidence($research_question_id = null) {
        $where_clause = "WHERE 1=1";
        $params = [];

        if ($research_question_id) {
            $where_clause .= " AND research_question_id = %d";
            $params[] = $research_question_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT id FROM {$this->table_name} {$where_clause}",
            ...$params
        );

        $analysis_ids = $this->wpdb->get_col($query);
        $updated_count = 0;

        foreach ($analysis_ids as $id) {
            $analysis = $this->find_by_id($id);
            if ($analysis) {
                $analysis->autoAssessConfidence();
                $updated_count++;
            }
        }

        return $updated_count;
    }

    /**
     * Get analysis statistics
     */
    public function get_statistics($file_id = null) {
        $where_clause = $file_id ? "WHERE file_id = %s" : "";
        $params = $file_id ? [$file_id] : [];

        $base_query = "FROM {$this->table_name} {$where_clause}";

        $stats = [];

        // Total count
        $stats['total_analyses'] = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) {$base_query}", ...$params)
        );

        // By evidence type
        $stats['by_evidence_type'] = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT evidence_type, COUNT(*) as count {$base_query} GROUP BY evidence_type",
                ...$params
            )
        );

        // By confidence level
        $stats['by_confidence'] = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT confidence_level, COUNT(*) as count {$base_query} GROUP BY confidence_level",
                ...$params
            )
        );

        // Average relevance score
        $stats['avg_relevance'] = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT AVG(relevance_score) {$base_query}", ...$params)
        );

        return $stats;
    }
}
