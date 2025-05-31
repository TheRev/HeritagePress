<?php
/**
 * Heritage Press Table Verification Tool
 *
 * This script analyzes the database to compare the tables that actually exist
 * in the WordPress database against what is defined in the plugin schema.
 * This helps identify discrepancies between the documented 19 tables and what you see.
 *
 * @package HeritagePress\Tools
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Heritage Press Table Verification class
 */
class Heritage_Press_Table_Verification {
    private $table_prefix;
    
    public function __construct() {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . 'heritage_press_';
    }
    
    /**
     * List all tables defined in the schema
     */
    public function get_defined_tables() {
        // These are the tables defined in class-database-manager.php
        return [
            'gedcom_trees',
            'submitters',
            'shared_notes',
            'individuals',
            'individual_names',
            'families',
            'events',
            'places',
            'family_children',
            'media',
            'sources',
            'citations',
            'citation_references',
            'repositories',
            'media_relationships',
            'individual_identifiers',
            'individual_facts',
            'family_facts',
            'associations',
            'audit_logs'  // This one was mentioned in PHASE1-COMPLETION-SUMMARY.md
        ];
    }
    
    /**
     * List all evidence tables that should have been removed
     */
    public function get_evidence_tables() {
        return [
            'research_questions',
            'information_statements',
            'evidence_analysis',
            'proof_arguments',
            'proof_evidence_links',
            'source_quality_assessments'
        ];
    }
    
    /**
     * Get all Heritage Press tables that actually exist in the database
     */
    public function get_existing_tables() {
        global $wpdb;
        
        // Get all tables in the database with our prefix
        $all_tables = $wpdb->get_col("SHOW TABLES LIKE '{$this->table_prefix}%'");
        
        // Strip prefix for easier comparison
        $existing_tables = [];
        foreach ($all_tables as $table) {
            $existing_tables[] = str_replace($this->table_prefix, '', $table);
        }
        
        return $existing_tables;
    }
    
    /**
     * Compare schema tables with actual tables
     */
    public function verify_tables() {
        $defined_tables = $this->get_defined_tables();
        $existing_tables = $this->get_existing_tables();
        $evidence_tables = $this->get_evidence_tables();
        
        $missing_tables = array_diff($defined_tables, $existing_tables);
        $unexpected_tables = array_diff($existing_tables, $defined_tables);
        $remaining_evidence = array_intersect($existing_tables, $evidence_tables);
        
        return [
            'defined' => $defined_tables,
            'existing' => $existing_tables,
            'missing' => $missing_tables,
            'unexpected' => $unexpected_tables,
            'evidence_remaining' => $remaining_evidence
        ];
    }
    
    /**
     * Display the verification results
     */
    public function display_results() {
        $results = $this->verify_tables();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Heritage Press Database Table Verification', 'heritage-press'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('This tool compares the tables defined in the plugin schema with those that actually exist in your WordPress database.', 'heritage-press'); ?></p>
            </div>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Table Verification Results', 'heritage-press'); ?></h2>
                
                <h3><?php _e('Summary', 'heritage-press'); ?></h3>
                <ul>
                    <li><?php echo sprintf(__('Total tables defined in schema: %d', 'heritage-press'), count($results['defined'])); ?></li>
                    <li><?php echo sprintf(__('Total tables in database: %d', 'heritage-press'), count($results['existing'])); ?></li>
                    <li><?php echo sprintf(__('Missing tables: %d', 'heritage-press'), count($results['missing'])); ?></li>
                    <li><?php echo sprintf(__('Unexpected tables: %d', 'heritage-press'), count($results['unexpected'])); ?></li>
                </ul>
                
                <h3><?php _e('Tables Defined in Schema', 'heritage-press'); ?></h3>
                <div style="max-height: 200px; overflow-y: auto; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; margin-bottom: 15px;">
                    <?php foreach ($results['defined'] as $table): ?>
                        <div style="padding: 3px 0;">
                            <?php echo esc_html($table); ?>
                            <?php if (in_array($table, $results['existing'])): ?>
                                <span style="color: green; margin-left: 10px;"><?php _e('✓ Exists', 'heritage-press'); ?></span>
                            <?php else: ?>
                                <span style="color: red; margin-left: 10px;"><?php _e('✗ Missing', 'heritage-press'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <h3><?php _e('Existing Tables in Database', 'heritage-press'); ?></h3>
                <div style="max-height: 200px; overflow-y: auto; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; margin-bottom: 15px;">
                    <?php foreach ($results['existing'] as $table): ?>
                        <div style="padding: 3px 0;">
                            <?php echo esc_html($table); ?>
                            <?php if (in_array($table, $results['defined'])): ?>
                                <span style="color: green; margin-left: 10px;"><?php _e('✓ Defined in schema', 'heritage-press'); ?></span>
                            <?php else: ?>
                                <span style="color: orange; margin-left: 10px;"><?php _e('! Unexpected table', 'heritage-press'); ?></span>
                            <?php endif; ?>
                            <?php if (in_array($table, $this->get_evidence_tables())): ?>
                                <span style="color: red; margin-left: 10px;"><?php _e('Evidence table that should be removed', 'heritage-press'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($results['missing']) > 0): ?>
                    <h3><?php _e('Missing Tables', 'heritage-press'); ?></h3>
                    <div style="padding: 10px; background-color: #ffeeee; border: 1px solid #ffcccc; margin-bottom: 15px;">
                        <?php foreach ($results['missing'] as $table): ?>
                            <div style="padding: 3px 0;">
                                <?php echo esc_html($table); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="description">
                        <?php _e('These tables are defined in the plugin schema but are missing from your database. This could be due to installation errors or incomplete feature activation.', 'heritage-press'); ?>
                    </p>
                <?php endif; ?>
                
                <?php if (count($results['evidence_remaining']) > 0): ?>
                    <h3><?php _e('Evidence Tables Not Removed', 'heritage-press'); ?></h3>
                    <div style="padding: 10px; background-color: #ffffee; border: 1px solid #ffeecc; margin-bottom: 15px;">
                        <?php foreach ($results['evidence_remaining'] as $table): ?>
                            <div style="padding: 3px 0;">
                                <?php echo esc_html($table); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="description">
                        <?php _e('These Evidence Explained tables should have been removed but are still present in your database.', 'heritage-press'); ?>
                    </p>
                <?php endif; ?>
                
                <h3><?php _e('Recommendation', 'heritage-press'); ?></h3>
                <?php if (count($results['missing']) > 0): ?>
                    <p><?php _e('Several tables are missing from your database. You should try:', 'heritage-press'); ?></p>
                    <ol>
                        <li><?php _e('Deactivate and reactivate the Heritage Press plugin', 'heritage-press'); ?></li>
                        <li><?php _e('Run the database update script: Admin → Heritage Press → Update Database', 'heritage-press'); ?></li>
                        <li><?php _e('Check your server\'s error log for any SQL errors during table creation', 'heritage-press'); ?></li>
                    </ol>
                <?php elseif (count($results['evidence_remaining']) > 0): ?>
                    <p><?php _e('Some Evidence Explained tables are still present in your database. You should:', 'heritage-press'); ?></p>
                    <ol>
                        <li><?php _e('Go to Heritage Press → Remove Evidence System to complete the removal', 'heritage-press'); ?></li>
                    </ol>
                <?php else: ?>
                    <p><?php _e('The tables in your database generally match what is expected after the Evidence Explained system removal.', 'heritage-press'); ?></p>
                    <p><?php _e('Note that the documentation mentions 19 tables, but the number of tables may vary depending on which features you\'ve activated.', 'heritage-press'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

// Initialize the verifier
$table_verifier = new Heritage_Press_Table_Verification();
$table_verifier->display_results();
