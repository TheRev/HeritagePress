<?php
/**
 * Proof Argument Repository Class
 *
 * Repository for managing Proof Arguments following Elizabeth Shown Mills' Evidence methodology.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Repositories;

use HeritagePress\Database\DatabaseManager;
use HeritagePress\Models\Proof_Argument;
use HeritagePress\Core\Audit_Log_Observer;

class Proof_Argument_Repository {

    private $wpdb;
    private $table_name;
    private $links_table_name;
    private $audit_observer;

    /**
     * Constructor
     */
    public function __construct(Audit_Log_Observer $audit_observer) {        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = DatabaseManager::get_table_prefix() . 'proof_arguments';
        $this->links_table_name = DatabaseManager::get_table_prefix() . 'proof_evidence_links';
        $this->audit_observer = $audit_observer;
    }

    /**
     * Converts a database row to a Proof_Argument model
     */
    private function to_model(?\stdClass $db_row): ?Proof_Argument {
        if (!$db_row) {
            return null;
        }
        return Proof_Argument::from_db_object($db_row);
    }

    /**
     * Create a new proof argument
     */
    public function create(array $data) {
        if (empty($data['research_question_id']) || empty($data['conclusion_statement'])) {
            return false;
        }

        $defaults = [
            'uuid' => wp_generate_uuid4(),
            'file_id' => '', // Will be inherited from research question
            'conclusion_type' => 'PROBABLE',
            'confidence_percentage' => 75,
            'methodology_used' => 'GPS (Genealogical Proof Standard)',
            'peer_review_status' => 'UNREVIEWED',
            'published_status' => 'DRAFT',
            'created_by_user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $data = array_merge($defaults, $data);

        // Get file_id from research question if not provided
        if (empty($data['file_id'])) {
            $question_table = DatabaseManager::get_table_prefix() . 'research_questions';
            $file_id = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT file_id FROM {$question_table} WHERE id = %d",
                    $data['research_question_id']
                )
            );
            $data['file_id'] = $file_id ?: '';
        }

        $result = $this->wpdb->insert($this->table_name, $data);

        if ($result === false) {
            return false;
        }

        $argument_id = $this->wpdb->insert_id;
        $argument = $this->find_by_id($argument_id);

        // Log audit trail
        $this->audit_observer->log_action('create', 'proof_argument', $argument_id, $data);

        return $argument;
    }

    /**
     * Find proof argument by ID
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
     * Find proof argument by UUID
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
     * Get proof arguments for a research question
     */
    public function find_by_research_question($research_question_id, $filters = []) {
        $where_clause = "WHERE research_question_id = %d";
        $params = [$research_question_id];

        if (!empty($filters['conclusion_type'])) {
            $where_clause .= " AND conclusion_type = %s";
            $params[] = $filters['conclusion_type'];
        }

        if (!empty($filters['peer_review_status'])) {
            $where_clause .= " AND peer_review_status = %s";
            $params[] = $filters['peer_review_status'];
        }

        if (!empty($filters['published_status'])) {
            $where_clause .= " AND published_status = %s";
            $params[] = $filters['published_status'];
        }

        if (!empty($filters['min_confidence'])) {
            $where_clause .= " AND confidence_percentage >= %d";
            $params[] = $filters['min_confidence'];
        }

        $order_by = isset($filters['order_by']) ? $filters['order_by'] : 'confidence_percentage DESC, created_at DESC';

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$order_by}",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get proof arguments by file ID
     */
    public function find_by_file($file_id, $filters = []) {
        $where_clause = "WHERE file_id = %s";
        $params = [$file_id];

        if (!empty($filters['peer_review_status'])) {
            $where_clause .= " AND peer_review_status = %s";
            $params[] = $filters['peer_review_status'];
        }

        if (!empty($filters['created_by'])) {
            $where_clause .= " AND created_by_user_id = %d";
            $params[] = $filters['created_by'];
        }

        $order_by = isset($filters['order_by']) ? $filters['order_by'] : 'created_at DESC';

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$order_by}",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Update proof argument
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
            $this->audit_observer->log_action('update', 'proof_argument', $id, $data);
            return $this->find_by_id($id);
        }

        return false;
    }

    /**
     * Add evidence to proof argument
     */
    public function add_evidence($argument_id, $evidence_analysis_id, $role = 'SUPPORTING', $weight = 'IMPORTANT', $notes = null) {
        $link_data = [
            'proof_argument_id' => $argument_id,
            'evidence_analysis_id' => $evidence_analysis_id,
            'evidence_role' => $role,
            'weight_in_argument' => $weight,
            'usage_notes' => $notes,
            'created_at' => current_time('mysql')
        ];

        $result = $this->wpdb->insert($this->links_table_name, $link_data);

        if ($result !== false) {
            $this->audit_observer->log_action('add_evidence', 'proof_argument', $argument_id, $link_data);
            return true;
        }

        return false;
    }

    /**
     * Remove evidence from proof argument
     */
    public function remove_evidence($argument_id, $evidence_analysis_id) {
        $result = $this->wpdb->delete(
            $this->links_table_name,
            [
                'proof_argument_id' => $argument_id,
                'evidence_analysis_id' => $evidence_analysis_id
            ],
            ['%d', '%d']
        );

        if ($result !== false) {
            $this->audit_observer->log_action('remove_evidence', 'proof_argument', $argument_id, [
                'evidence_analysis_id' => $evidence_analysis_id
            ]);
            return true;
        }

        return false;
    }

    /**
     * Update evidence role/weight in argument
     */
    public function update_evidence_role($argument_id, $evidence_analysis_id, $role = null, $weight = null, $notes = null) {
        $update_data = [];
        
        if ($role !== null) $update_data['evidence_role'] = $role;
        if ($weight !== null) $update_data['weight_in_argument'] = $weight;
        if ($notes !== null) $update_data['usage_notes'] = $notes;

        if (empty($update_data)) return false;

        $result = $this->wpdb->update(
            $this->links_table_name,
            $update_data,
            [
                'proof_argument_id' => $argument_id,
                'evidence_analysis_id' => $evidence_analysis_id
            ],
            null,
            ['%d', '%d']
        );

        if ($result !== false) {
            $this->audit_observer->log_action('update_evidence_role', 'proof_argument', $argument_id, $update_data);
            return true;
        }

        return false;
    }

    /**
     * Get evidence links for a proof argument
     */
    public function get_evidence_links($argument_id) {
        $query = $this->wpdb->prepare(
            "SELECT pel.*, ea.uuid as evidence_uuid, ea.evidence_type, ea.confidence_level
            FROM {$this->links_table_name} pel
            JOIN " . DatabaseManager::get_table_prefix() . "evidence_analysis ea ON pel.evidence_analysis_id = ea.id
            WHERE pel.proof_argument_id = %d
            ORDER BY pel.evidence_role, pel.weight_in_argument",
            $argument_id
        );

        return $this->wpdb->get_results($query);
    }

    /**
     * Submit for peer review
     */
    public function submit_for_review($id, $reviewer_user_id = null) {
        $update_data = [
            'peer_review_status' => 'UNDER_REVIEW',
            'updated_at' => current_time('mysql')
        ];

        if ($reviewer_user_id) {
            $update_data['reviewed_by_user_id'] = $reviewer_user_id;
        }

        return $this->update($id, $update_data);
    }

    /**
     * Complete peer review
     */
    public function complete_review($id, $approved, $review_notes = null) {
        $update_data = [
            'peer_review_status' => $approved ? 'APPROVED' : 'REJECTED',
            'review_notes' => $review_notes,
            'review_date' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $result = $this->update($id, $update_data);

        if ($result && $approved) {
            // Mark related research question as resolved
            $argument = $this->find_by_id($id);
            if ($argument && $argument->research_question_id) {
                $question_repo = new Research_Question_Repository($this->audit_observer);
                $question_repo->mark_resolved($argument->research_question_id, $id);
            }
        }

        return $result;
    }

    /**
     * Get arguments awaiting review
     */
    public function get_awaiting_review($file_id = null) {
        $where_clause = "WHERE peer_review_status = 'UNDER_REVIEW'";
        $params = [];

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY created_at ASC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get approved arguments
     */
    public function get_approved_arguments($file_id = null, $published_only = false) {
        $where_clause = "WHERE peer_review_status = 'APPROVED'";
        $params = [];

        if ($published_only) {
            $where_clause .= " AND published_status IN ('INTERNAL', 'PUBLIC')";
        }

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY confidence_percentage DESC, created_at DESC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get GPS compliance statistics
     */
    public function get_gps_compliance_stats($file_id = null) {
        $where_clause = $file_id ? "WHERE file_id = %s" : "";
        $params = $file_id ? [$file_id] : [];

        $query = $this->wpdb->prepare(
            "SELECT 
                conclusion_type,
                peer_review_status,
                AVG(confidence_percentage) as avg_confidence,
                COUNT(*) as count
            FROM {$this->table_name} 
            {$where_clause}
            GROUP BY conclusion_type, peer_review_status
            ORDER BY conclusion_type, peer_review_status",
            ...$params
        );

        return $this->wpdb->get_results($query);
    }

    /**
     * Get strongest proof arguments
     */
    public function get_strongest_arguments($file_id = null, $limit = 10) {
        $where_clause = "WHERE peer_review_status = 'APPROVED'";
        $params = [];

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT *, 
                (confidence_percentage + 
                 CASE conclusion_type
                    WHEN 'PROVEN' THEN 20
                    WHEN 'PROBABLE' THEN 15
                    WHEN 'POSSIBLE' THEN 10
                    ELSE 0
                 END) as strength_score
            FROM {$this->table_name} 
            {$where_clause}
            ORDER BY strength_score DESC, created_at DESC
            LIMIT %d",
            ...array_merge($params, [$limit])
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Delete proof argument
     */
    public function delete($id) {
        // First delete evidence links
        $this->wpdb->delete(
            $this->links_table_name,
            ['proof_argument_id' => $id],
            ['%d']
        );

        // Log the deletion
        $argument = $this->find_by_id($id);
        if ($argument) {
            $this->audit_observer->log_action('delete', 'proof_argument', $id, []);
        }

        $result = $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get arguments needing attention
     */
    public function get_arguments_needing_attention($file_id = null) {
        $where_clause = "WHERE (
            (peer_review_status = 'UNREVIEWED' AND DATEDIFF(NOW(), created_at) > 7) OR
            (peer_review_status = 'REJECTED' AND review_date IS NOT NULL) OR
            (confidence_percentage < 50 AND conclusion_type IN ('PROVEN', 'PROBABLE'))
        )";
        $params = [];

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY created_at ASC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get argument statistics
     */
    public function get_statistics($file_id = null) {
        $where_clause = $file_id ? "WHERE file_id = %s" : "";
        $params = $file_id ? [$file_id] : [];

        $base_query = "FROM {$this->table_name} {$where_clause}";

        $stats = [];

        // Total count
        $stats['total_arguments'] = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) {$base_query}", ...$params)
        );

        // By conclusion type
        $stats['by_conclusion_type'] = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT conclusion_type, COUNT(*) as count {$base_query} GROUP BY conclusion_type",
                ...$params
            )
        );

        // By review status
        $stats['by_review_status'] = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT peer_review_status, COUNT(*) as count {$base_query} GROUP BY peer_review_status",
                ...$params
            )
        );

        // Average confidence
        $stats['avg_confidence'] = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT AVG(confidence_percentage) {$base_query}", ...$params)
        );

        return $stats;
    }
}
