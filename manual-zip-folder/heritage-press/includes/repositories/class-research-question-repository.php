<?php
/**
 * Research Question Repository Class
 *
 * Repository for managing Research Questions following Elizabeth Shown Mills' Evidence methodology.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Repositories;

use HeritagePress\Database\DatabaseManager; // Corrected: No underscore
use HeritagePress\Models\Research_Question;
use HeritagePress\Core\Audit_Log_Observer;

class Research_Question_Repository {

    private $wpdb;
    private $table_name;
    private $audit_observer;

    /**
     * Constructor
     */
    public function __construct(Audit_Log_Observer $audit_observer) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = DatabaseManager::get_table_prefix() . 'research_questions'; // Corrected: No underscore
        $this->audit_observer = $audit_observer;
    }

    /**
     * Converts a database row to a Research_Question model
     */
    private function to_model(?\stdClass $db_row): ?Research_Question {
        if (!$db_row) {
            return null;
        }
        return Research_Question::from_db_object($db_row);
    }

    /**
     * Create a new research question
     */
    public function create(array $data) {
        if (empty($data['question_text']) || empty($data['file_id'])) {
            return false;
        }

        $defaults = [
            'uuid' => wp_generate_uuid4(),
            'question_type' => 'OTHER',
            'status' => 'OPEN',
            'priority' => 'MEDIUM',
            'created_by_user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $data = array_merge($defaults, $data);

        $result = $this->wpdb->insert($this->table_name, $data);

        if ($result === false) {
            return false;
        }

        $question_id = $this->wpdb->insert_id;
        $question = $this->find_by_id($question_id);

        // Log audit trail
        $this->audit_observer->log_action('create', 'research_question', $question_id, $data);

        return $question;
    }

    /**
     * Find research question by ID
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
     * Find research question by UUID
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
     * Get research questions for a specific individual
     */
    public function find_by_individual($individual_id, $status = null) {
        $where_clause = "WHERE individual_id = %d";
        $params = [$individual_id];

        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY priority DESC, created_at DESC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get research questions for a specific family
     */
    public function find_by_family($family_id, $status = null) {
        $where_clause = "WHERE family_id = %d";
        $params = [$family_id];

        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY priority DESC, created_at DESC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Get research questions by file ID
     */
    public function find_by_file($file_id, $filters = []) {
        $where_clause = "WHERE file_id = %s";
        $params = [$file_id];

        if (!empty($filters['status'])) {
            $where_clause .= " AND status = %s";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where_clause .= " AND priority = %s";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['question_type'])) {
            $where_clause .= " AND question_type = %s";
            $params[] = $filters['question_type'];
        }

        if (!empty($filters['assigned_to'])) {
            $where_clause .= " AND assigned_to_user_id = %d";
            $params[] = $filters['assigned_to'];
        }

        $order_by = isset($filters['order_by']) ? $filters['order_by'] : 'priority DESC, created_at DESC';

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$order_by}",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Update research question
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
            $this->audit_observer->log_action('update', 'research_question', $id, $data);
            return $this->find_by_id($id);
        }

        return false;
    }

    /**
     * Mark question as resolved
     */
    public function mark_resolved($id, $proof_argument_id = null) {
        $update_data = [
            'status' => 'RESOLVED',
            'updated_at' => current_time('mysql')
        ];

        if ($proof_argument_id) {
            $update_data['research_notes'] = $this->wpdb->get_var(
                $this->wpdb->prepare("SELECT research_notes FROM {$this->table_name} WHERE id = %d", $id)
            ) . "\n\nResolved with proof argument ID: {$proof_argument_id}";
        }

        return $this->update($id, $update_data);
    }

    /**
     * Get research progress statistics
     */
    public function get_research_statistics($file_id = null) {
        $where_clause = $file_id ? "WHERE file_id = %s" : "";
        $params = $file_id ? [$file_id] : [];

        $query = $this->wpdb->prepare(
            "SELECT 
                status,
                priority,
                question_type,
                COUNT(*) as count
            FROM {$this->table_name} 
            {$where_clause}
            GROUP BY status, priority, question_type",
            ...$params
        );

        return $this->wpdb->get_results($query);
    }

    /**
     * Get overdue research questions
     */
    public function get_overdue_questions($file_id = null) {
        $where_clause = "WHERE target_resolution_date < %s AND status = 'OPEN'";
        $params = [current_time('mysql')];

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY target_resolution_date ASC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }

    /**
     * Delete research question
     */
    public function delete($id) {
        // First log the deletion
        $question = $this->find_by_id($id);
        if ($question) {
            $this->audit_observer->log_action('delete', 'research_question', $id, []);
        }

        $result = $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get questions needing attention (high priority, overdue, or long-running)
     */
    public function get_questions_needing_attention($file_id = null) {
        $where_clause = "WHERE (
            (priority = 'HIGH' AND status = 'OPEN') OR
            (target_resolution_date < %s AND status = 'OPEN') OR
            (DATEDIFF(NOW(), created_at) > 30 AND status = 'OPEN')
        )";
        $params = [current_time('mysql')];

        if ($file_id) {
            $where_clause .= " AND file_id = %s";
            $params[] = $file_id;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY priority DESC, target_resolution_date ASC",
            ...$params
        );

        $rows = $this->wpdb->get_results($query);
        return array_map([$this, 'to_model'], $rows);
    }
}
