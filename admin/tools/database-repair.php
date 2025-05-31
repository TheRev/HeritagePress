<?php
/**
 * Heritage Press Database Repair Tool
 *
 * This tool analyzes the Heritage Press database schema and recreates any missing tables.
 * It helps address the discrepancy between the 19 tables defined in the schema and
 * what's actually present in the database.
 *
 * @package HeritagePress\Tools
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Heritage Press Database Repair class
 */
class Heritage_Press_Database_Repair {
    private $table_prefix;
    private $schema_tables;
    private $existing_tables;
    private $missing_tables;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . 'heritage_press_';
        $this->analyze_tables();
    }
    
    /**
     * Analyze the database to identify tables
     */
    private function analyze_tables() {
        // Get all tables defined in the schema
        $this->schema_tables = $this->get_schema_tables();
        
        // Get existing tables
        $this->existing_tables = $this->get_existing_tables();
        
        // Find missing tables
        $this->missing_tables = array_diff($this->schema_tables, $this->existing_tables);
    }
    
    /**
     * Get all tables defined in the schema
     */
    private function get_schema_tables() {
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
            'audit_logs'
        ];
    }
    
    /**
     * Get all Heritage Press tables that actually exist in the database
     */
    private function get_existing_tables() {
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
     * Repair missing tables
     */
    public function repair_tables() {
        if (empty($this->missing_tables)) {
            return ['success' => true, 'message' => __('All tables exist. No repair needed.', 'heritage-press'), 'repaired' => []];
        }
        
        $results = [
            'success' => true,
            'repaired' => [],
            'failed' => [],
        ];
        
        // Get database manager
        require_once HERITAGE_PRESS_PLUGIN_DIR . 'includes/database/class-database-manager.php';
        $db_manager = new \HeritagePress\Database\Database_Manager();
        
        // Create tables
        $db_manager->create_tables();
        
        // Check if tables were created
        $post_repair_tables = $this->get_existing_tables();
        
        foreach ($this->missing_tables as $missing_table) {
            if (in_array($missing_table, $post_repair_tables)) {
                $results['repaired'][] = $missing_table;
            } else {
                $results['failed'][] = $missing_table;
                $results['success'] = false;
            }
        }
        
        // Set overall message
        if ($results['success']) {
            $results['message'] = __('All missing tables have been successfully created.', 'heritage-press');
        } else {
            $results['message'] = __('Some tables could not be created. Please check server error logs.', 'heritage-press');
        }
        
        return $results;
    }
    
    /**
     * Display the database analysis
     */
    public function display_analysis() {
        ?>
        <div class="wrap">
            <h1><?php _e('Heritage Press Database Table Analysis', 'heritage-press'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('This tool analyzes your Heritage Press database tables and can repair any missing tables.', 'heritage-press'); ?></p>
            </div>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Database Analysis', 'heritage-press'); ?></h2>
                
                <p><?php echo sprintf(__('Total tables defined in schema: %d', 'heritage-press'), count($this->schema_tables)); ?></p>
                <p><?php echo sprintf(__('Total tables existing in database: %d', 'heritage-press'), count($this->existing_tables)); ?></p>
                
                <?php if (empty($this->missing_tables)): ?>
                    <div class="notice notice-success" style="margin: 10px 0;">
                        <p><?php _e('All required tables exist in your database.', 'heritage-press'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="notice notice-error" style="margin: 10px 0;">
                        <p><?php echo sprintf(__('%d tables are missing from your database:', 'heritage-press'), count($this->missing_tables)); ?></p>
                    </div>
                    
                    <h3><?php _e('Missing Tables', 'heritage-press'); ?></h3>
                    <ul style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd;">
                        <?php foreach ($this->missing_tables as $table): ?>
                            <li><code><?php echo esc_html($this->table_prefix . $table); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('heritage_press_repair_tables', 'repair_nonce'); ?>
                        <input type="hidden" name="action" value="heritage_press_repair_tables">
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Repair Missing Tables', 'heritage-press'); ?>">
                        </p>
                    </form>
                <?php endif; ?>
                
                <h3><?php _e('Existing Tables', 'heritage-press'); ?></h3>
                <div style="max-height: 200px; overflow-y: auto; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd;">
                    <?php foreach ($this->existing_tables as $table): ?>
                        <div style="padding: 3px 0;"><code><?php echo esc_html($this->table_prefix . $table); ?></code></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Process form submission to repair tables
     */
    public function process_form() {
        // Check if form was submitted
        if (!isset($_POST['action']) || $_POST['action'] !== 'heritage_press_repair_tables') {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['repair_nonce']) || !wp_verify_nonce($_POST['repair_nonce'], 'heritage_press_repair_tables')) {
            wp_die(__('Security check failed', 'heritage-press'));
        }
        
        // Perform the repair
        $results = $this->repair_tables();
        
        // Display results
        ?>
        <div class="wrap">
            <h1><?php _e('Heritage Press Database Repair Results', 'heritage-press'); ?></h1>
            
            <?php if ($results['success']): ?>
                <div class="notice notice-success">
                    <p><?php echo esc_html($results['message']); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html($results['message']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($results['repaired'])): ?>
                <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                    <h3><?php _e('Successfully Created Tables', 'heritage-press'); ?></h3>
                    <ul style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd;">
                        <?php foreach ($results['repaired'] as $table): ?>
                            <li><code><?php echo esc_html($this->table_prefix . $table); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($results['failed'])): ?>
                <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                    <h3><?php _e('Failed to Create Tables', 'heritage-press'); ?></h3>
                    <ul style="background-color: #ffeeee; padding: 10px; border: 1px solid #ffcccc;">
                        <?php foreach ($results['failed'] as $table): ?>
                            <li><code><?php echo esc_html($this->table_prefix . $table); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <p><?php _e('Common causes for failures:', 'heritage-press'); ?></p>
                    <ul>
                        <li><?php _e('Database user lacks permissions to create tables', 'heritage-press'); ?></li>
                        <li><?php _e('Foreign key constraints referencing tables that don\'t exist yet', 'heritage-press'); ?></li>
                        <li><?php _e('SQL syntax errors in the table creation script', 'heritage-press'); ?></li>
                    </ul>
                    
                    <p><?php _e('You may need to check your server\'s error log for detailed SQL error messages.', 'heritage-press'); ?></p>
                </div>
            <?php endif; ?>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=heritage-database-repair'); ?>" class="button">
                    <?php _e('Back to Database Analysis', 'heritage-press'); ?>
                </a>
            </p>
        </div>
        <?php
        exit; // Stop execution to prevent the analysis from showing after the results
    }
}

// Initialize and run the repair tool
$repair_tool = new Heritage_Press_Database_Repair();
$repair_tool->process_form();
$repair_tool->display_analysis();
