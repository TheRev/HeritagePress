<?php
/**
 * Proof Argument Repository
 *
 * Handles database operations for proof arguments using Mills methodology.
 *
 * @package HeritagePress\Repositories
 */

namespace HeritagePress\Repositories;

use HeritagePress\Core\AuditLogObserver;

class Proof_Argument_Repository {
    
    private $wpdb;
    private $table_name;
    private $audit_observer;
    
    public function __construct(AuditLogObserver $audit_observer) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'heritage_proof_arguments';
        $this->audit_observer = $audit_observer;
    }

    /**
     * Find proof arguments by research question
     */
    public function find_by_research_question($research_question_id, $filters = []) {
        global $wpdb;
        
        $where_clauses = ["research_question_id = %d"];
        $where_values = [$research_question_id];
        
        if (!empty($filters['argument_type'])) {
            $where_clauses[] = "conclusion_type = %s";
            $where_values[] = $filters['argument_type'];
        }
        
        if (!empty($filters['confidence_level'])) {
            $where_clauses[] = "confidence_percentage >= %d";
            $where_values[] = $filters['confidence_level'];
        }
        
        if (!empty($filters['peer_review_status'])) {
            $where_clauses[] = "peer_review_status = %s";
            $where_values[] = $filters['peer_review_status'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
          $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY created_at DESC",
            $where_values
        );
        
        return $wpdb->get_results($sql);
    }

    /**
     * Find proof arguments by file
     */
    public function find_by_file($file_id, $filters = []) {
        global $wpdb;
        
        $where_clauses = ["file_id = %s"];
        $where_values = [$file_id];
        
        if (!empty($filters['research_question_id'])) {
            $where_clauses[] = "research_question_id = %d";
            $where_values[] = $filters['research_question_id'];
        }
        
        if (!empty($filters['argument_type'])) {
            $where_clauses[] = "conclusion_type = %s";
            $where_values[] = $filters['argument_type'];
        }
        
        if (!empty($filters['confidence_level'])) {
            $where_clauses[] = "confidence_percentage >= %d";
            $where_values[] = $filters['confidence_level'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY created_at DESC",
            $where_values
        );
          return $wpdb->get_results($sql);
    }

    /**
     * Find proof argument by ID
     */
    public function find_by_id($id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );
        
        return $wpdb->get_row($sql);
    }

    /**
     * Get all proof arguments with optional filters
     */
    public function get_all($filters = []) {
        global $wpdb;
        
        $where_clauses = ["1=1"];
        $where_values = [];
        
        if (!empty($filters['research_question_id'])) {
            $where_clauses[] = "research_question_id = %d";
            $where_values[] = $filters['research_question_id'];
        }
        
        if (!empty($filters['argument_type'])) {
            $where_clauses[] = "conclusion_type = %s";
            $where_values[] = $filters['argument_type'];
        }
        
        if (!empty($filters['confidence_level'])) {
            $where_clauses[] = "confidence_percentage >= %d";
            $where_values[] = $filters['confidence_level'];
        }
        
        if (!empty($filters['peer_review_status'])) {
            $where_clauses[] = "peer_review_status = %s";
            $where_values[] = $filters['peer_review_status'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
          if (!empty($where_values)) {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY created_at DESC",
                $where_values
            );
        } else {
            $sql = "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY created_at DESC";
        }
        
        return $wpdb->get_results($sql);
    }

    /**
     * Create a new proof argument
     */
    public function create($data) {
        global $wpdb;
        
        $defaults = [
            'uuid' => wp_generate_uuid4(),
            'created_by_user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'peer_review_status' => 'UNREVIEWED',
            'published_status' => 'DRAFT',
            'confidence_percentage' => 50
        ];
        
        $data = wp_parse_args($data, $defaults);
          $result = $wpdb->insert(
            $this->table_name,
            $data
        );
          if ($result !== false) {
            $proof_id = $wpdb->insert_id;
            // TODO: Implement audit logging
            return $proof_id;
        }
        
        return false;
    }

    /**
     * Update a proof argument
     */
    public function update($id, $data) {
        global $wpdb;
        
        $old_data = $this->find_by_id($id);
        if (!$old_data) {
            return false;
        }
        
        $data['updated_at'] = current_time('mysql');
          $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id]
        );
          if ($result !== false) {
            // TODO: Implement audit logging
            return true;
        }
        
        return false;
    }

    /**
     * Delete a proof argument
     */
    public function delete($id) {
        global $wpdb;
        
        $old_data = $this->find_by_id($id);
        if (!$old_data) {
            return false;
        }
          $result = $wpdb->delete(
            $this->table_name,
            ['id' => $id]
        );
          if ($result !== false) {
            // Also delete related evidence links
            $wpdb->delete(
                $wpdb->prefix . 'heritage_proof_evidence_links',
                ['proof_argument_id' => $id]
            );
            
            // TODO: Implement proper audit logging via model events
            return true;
        }
        
        return false;
    }

    /**
     * Bulk delete proof arguments
     */
    public function bulk_delete($ids) {
        if (empty($ids)) {
            return false;
        }
        
        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
          // TODO: Implement proper audit logging via model events
        // foreach ($ids as $id) {
        //     $old_data = $this->find_by_id($id);
        //     if ($old_data) {
        //         // Log deletion when model events are properly implemented
        //     }
        // }
        
        // Delete evidence links first
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}proof_evidence_links WHERE proof_argument_id IN ({$placeholders})",
            $ids
        ));
          // Delete proof arguments
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE id IN ({$placeholders})",
            $ids
        ));
    }

    /**
     * Get proof arguments for GPS compliance assessment
     */
    public function get_for_gps_assessment($research_question_id = null) {
        global $wpdb;
        
        $where_sql = "1=1";
        $where_values = [];
        
        if ($research_question_id) {
            $where_sql = "research_question_id = %d";
            $where_values[] = $research_question_id;
        }
          if (!empty($where_values)) {
            $sql = $wpdb->prepare(
                "SELECT pa.*, COUNT(pel.evidence_analysis_id) as evidence_count 
                 FROM {$this->table_name} pa 
                 LEFT JOIN {$wpdb->prefix}heritage_proof_evidence_links pel ON pa.id = pel.proof_argument_id 
                 WHERE {$where_sql} 
                 GROUP BY pa.id 
                 ORDER BY pa.created_at DESC",
                $where_values
            );
        } else {
            $sql = "SELECT pa.*, COUNT(pel.evidence_analysis_id) as evidence_count 
                    FROM {$this->table_name} pa 
                    LEFT JOIN {$wpdb->prefix}heritage_proof_evidence_links pel ON pa.id = pel.proof_argument_id 
                    WHERE {$where_sql} 
                    GROUP BY pa.id 
                    ORDER BY pa.created_at DESC";
        }
        
        return $wpdb->get_results($sql);
    }

    /**
     * Get format array for wpdb operations
     */
    private function get_format_array($data) {
        $formats = [];
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'id':
                case 'research_question_id':
                case 'created_by_user_id':
                case 'reviewed_by_user_id':
                case 'confidence_percentage':
                    $formats[] = '%d';
                    break;
                default:
                    $formats[] = '%s';
                    break;
            }
        }
        return $formats;
    }
}
