<?php
namespace HeritagePress\Repositories;

use HeritagePress\Core\AuditLogObserver;

class Research_Question_Repository {
    private $wpdb;
    private $table_name;
    private $audit_observer;

    public function __construct(AuditLogObserver $audit_observer) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'heritage_research_questions'; // Assuming this is the correct table name
        $this->audit_observer = $audit_observer;
    }

    public function find_by_id($question_id) {
        if (empty($question_id)) {
            return null;
        }
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $question_id)
        );
    }

    public function find_by_file($file_id, $filters = []) {
        if (empty($file_id)) {
            return [];
        }
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE file_id = %s", $file_id);
        // Add other filters if necessary
        return $this->wpdb->get_results($sql);
    }

    public function get_all($filters = []) {
        $sql = "SELECT * FROM {$this->table_name}";
        // Add filters if necessary
        return $this->wpdb->get_results($sql);
    }

    public function create($data) {
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        $result = $this->wpdb->insert($this->table_name, $data);
        if ($result) {
            $new_id = $this->wpdb->insert_id;
            $this->audit_observer->log_action('create', 'research_question', $new_id, $data);
            return $new_id;
        }
        return false;
    }

    public function update($question_id, $data) {
        if (empty($question_id)) {
            return false;
        }
        $data['updated_at'] = current_time('mysql');
        $old_data = $this->find_by_id($question_id); // For logging

        $result = $this->wpdb->update($this->table_name, $data, ['id' => $question_id]);
        if ($result !== false) { // Can be 0 if no rows updated but still successful query
            $this->audit_observer->log_action('update', 'research_question', $question_id, $data, $old_data);
            return true;
        }
        return false;
    }

    public function delete($question_id) {
        if (empty($question_id)) {
            return false;
        }
        $old_data = $this->find_by_id($question_id); // For logging
        $result = $this->wpdb->delete($this->table_name, ['id' => $question_id]);
        if ($result) {
            $this->audit_observer->log_action('delete', 'research_question', $question_id, $old_data);
            return true;
        }
        return false;
    }
}
