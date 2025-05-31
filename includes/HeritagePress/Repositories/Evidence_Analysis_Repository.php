<?php
namespace HeritagePress\Repositories;

use HeritagePress\Core\AuditLogObserver;

class Evidence_Analysis_Repository {
    private $wpdb;
    private $table_name;
    private $audit_observer;

    public function __construct(AuditLogObserver $audit_observer) {
        global $wpdb;
        $this->wpdb = $wpdb;
        // Assuming the table name is 'heritage_evidence_analysis' based on the old model's table property
        $this->table_name = $this->wpdb->prefix . 'heritage_evidence_analysis'; 
        $this->audit_observer = $audit_observer;
    }

    public function find_by_id($analysis_id) {
        if (empty($analysis_id)) {
            return null;
        }
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $analysis_id)
        );
    }

    public function find_by_file($file_id, $filters = []) {
        if (empty($file_id)) {
            return [];
        }
        // Basic query, filters can be added here
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE file_id = %s", $file_id);
        if (!empty($filters['research_question_id'])) {
            $sql .= $this->wpdb->prepare(" AND research_question_id = %d", $filters['research_question_id']);
        }
        if (!empty($filters['evidence_type'])) {
            $sql .= $this->wpdb->prepare(" AND evidence_type = %s", $filters['evidence_type']);
        }
        // Add other filters as needed
        return $this->wpdb->get_results($sql);
    }

    public function find_by_research_question($research_question_id) {
        if (empty($research_question_id)) {
            return [];
        }
        return $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE research_question_id = %d ORDER BY created_at DESC", $research_question_id)
        );
    }

    public function get_all($filters = []) {
        $sql = "SELECT * FROM {$this->table_name} WHERE 1=1";
        if (!empty($filters['research_question_id'])) {
            $sql .= $this->wpdb->prepare(" AND research_question_id = %d", $filters['research_question_id']);
        }
        if (!empty($filters['evidence_type'])) {
            $sql .= $this->wpdb->prepare(" AND evidence_type = %s", $filters['evidence_type']);
        }
        // Add other filters as needed
        $sql .= " ORDER BY created_at DESC";
        return $this->wpdb->get_results($sql);
    }

    public function create($data) {
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        $result = $this->wpdb->insert($this->table_name, $data);
        if ($result) {
            $new_id = $this->wpdb->insert_id;
            $this->audit_observer->log_action('create', 'evidence_analysis', $new_id, $data);
            return $new_id;
        }
        return false;
    }

    public function update($analysis_id, $data) {
        if (empty($analysis_id)) {
            return false;
        }
        $data['updated_at'] = current_time('mysql');
        $old_data = $this->find_by_id($analysis_id); // For logging

        $result = $this->wpdb->update($this->table_name, $data, ['id' => $analysis_id]);
        if ($result !== false) {
            $this->audit_observer->log_action('update', 'evidence_analysis', $analysis_id, $data, $old_data);
            return true;
        }
        return false;
    }

    public function delete($analysis_id) {
        if (empty($analysis_id)) {
            return false;
        }
        $old_data = $this->find_by_id($analysis_id); // For logging
        $result = $this->wpdb->delete($this->table_name, ['id' => $analysis_id]);
        if ($result) {
            $this->audit_observer->log_action('delete', 'evidence_analysis', $analysis_id, $old_data);
            return true;
        }
        return false;
    }
}
