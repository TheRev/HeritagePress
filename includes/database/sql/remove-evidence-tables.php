<?php
/**
 * SQL script to remove Evidence Explained specific tables
 *
 * This script drops tables that are specific to the Evidence Explained methodology,
 * removing them as part of the transition to a standard genealogy plugin.
 *
 * @package HeritagePress\Database
 */

namespace HeritagePress\Database;

class Evidence_Table_Remover {    /**
     * Drop all Evidence Explained specific tables
     */
    public function drop_evidence_tables() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . 'heritage_press_';
        
        // The order is important for foreign key constraints
        $tables_to_drop = [
            'proof_evidence_links',        // Drop linking tables first
            'source_quality_assessments',  // Assessments before sources
            'proof_arguments',             // Arguments before questions
            'evidence_analysis',           // Analysis before information
            'information_statements',      // Information before questions
            'research_questions'           // Questions last
        ];
        
        // Safety check - verify we're not accidentally deleting core tables
        $protected_tables = [
            'individuals', 'families', 'sources', 'citations', 'events', 'places',
            'gedcom_trees', 'submitters', 'family_children'
        ];
        
        foreach ($tables_to_drop as $table) {
            // Safety check to ensure we don't drop core tables
            if (in_array($table, $protected_tables)) {
                throw new \Exception("ERROR: Attempting to drop protected table: $table. This is a core table and should not be removed.");
            }
            
            $wpdb->query("DROP TABLE IF EXISTS {$table_prefix}{$table}");
        }
    }
    
    /**
     * Create a foreign key fixer for any broken relationships after table removal
     */
    public function fix_foreign_keys() {
        global $wpdb;
        
        // Fix any potential foreign key issues - clear references to removed tables
        $wpdb->query("UPDATE {$wpdb->prefix}heritage_press_citations SET shared_note_id = NULL WHERE shared_note_id IS NOT NULL");
        $wpdb->query("UPDATE {$wpdb->prefix}heritage_press_sources SET shared_note_id = NULL WHERE shared_note_id IS NOT NULL");
    }
    
    /**
     * Migrate any valuable data from Evidence Explained tables to core tables
     * before dropping the tables
     */
    public function migrate_valuable_data() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . 'heritage_press_';
        
        // Check if information_statements table exists
        $info_statements_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_prefix}information_statements'") === $table_prefix . 'information_statements';
        if ($info_statements_exists) {
            // Migrate valuable citation data from information statements to citation notes
            $wpdb->query("
                UPDATE {$table_prefix}citations c
                JOIN {$table_prefix}information_statements i ON c.source_id = i.source_id
                SET c.notes = CONCAT_WS('\n\n', c.notes, 
                    CONCAT('Information Statement: ', i.statement_text))
                WHERE i.statement_text IS NOT NULL AND i.statement_text != ''
            ");
            
            // Log the migration
            error_log("Migrated information statement data to citation notes");
        }
        
        // Check if evidence_analysis table exists
        $evidence_analysis_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_prefix}evidence_analysis'") === $table_prefix . 'evidence_analysis';
        if ($evidence_analysis_exists) {
            // Migrate valuable analysis data to source notes
            $wpdb->query("
                UPDATE {$table_prefix}sources s
                JOIN {$table_prefix}information_statements i ON s.id = i.source_id
                JOIN {$table_prefix}evidence_analysis e ON i.id = e.information_statement_id
                SET s.notes = CONCAT_WS('\n\n', s.notes, 
                    CONCAT('Analysis: ', e.interpretation_notes))
                WHERE e.interpretation_notes IS NOT NULL AND e.interpretation_notes != ''
            ");
            
            // Log the migration
            error_log("Migrated evidence analysis data to source notes");
        }
        
        // Return count of migrated records
        return [
            'information_statements' => $info_statements_exists ? 
                $wpdb->get_var("SELECT COUNT(*) FROM {$table_prefix}information_statements") : 0,
            'evidence_analysis' => $evidence_analysis_exists ? 
                $wpdb->get_var("SELECT COUNT(*) FROM {$table_prefix}evidence_analysis") : 0
        ];
    }
}
