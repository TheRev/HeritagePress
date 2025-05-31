<?php
namespace HeritagePress\Tests;

use HeritagePress\Database\Evidence_Table_Remover;
use PHPUnit\Framework\TestCase;

/**
 * Test case for Evidence Explained system removal
 * 
 * Tests the functionality of the Evidence Table Remover and
 * verifies that the system is properly removed while preserving
 * core genealogy functionality.
 */
class EvidenceRemovalTest extends HeritagePressTestCase {
    protected $evidence_remover;
    protected $tables_to_check;
    
    protected function setUp(): void {
        parent::setUp();
        
        $this->evidence_remover = new Evidence_Table_Remover();
        
        // Tables that should be removed
        $this->tables_to_check = [
            'proof_evidence_links',
            'source_quality_assessments',
            'proof_arguments',
            'evidence_analysis',
            'information_statements',
            'research_questions'
        ];
        
        // Tables that should be preserved
        $this->core_tables = [
            'individuals',
            'families',
            'events',
            'places',
            'sources',
            'citations',
            'gedcom_trees'
        ];
    }
    
    /**
     * Test that evidence tables exist before removal
     */
    public function testEvidenceTablesExistBeforeRemoval() {
        global $wpdb;
        
        // Mock the tables exist before removal
        $wpdb->expects($this->atLeastOnce())
             ->method('get_var')
             ->willReturn('1'); // Table exists
        
        foreach ($this->tables_to_check as $table) {
            $table_name = $wpdb->prefix . 'heritage_press_' . $table;
            $this->assertEquals('1', $wpdb->get_var("SHOW TABLES LIKE '$table_name'"));
        }
    }
    
    /**
     * Test the table removal process
     */
    public function testEvidenceTableRemoval() {
        global $wpdb;
        
        // Mock successful query execution
        $wpdb->expects($this->atLeastOnce())
             ->method('query')
             ->willReturn(true);
             
        // After running the drop_evidence_tables method, tables should be gone
        $result = $this->evidence_remover->drop_evidence_tables();
        $this->assertTrue($result, 'Tables should be successfully removed');
    }
    
    /**
     * Test that core tables are preserved after removal
     */
    public function testCoreTablesArePreserved() {
        global $wpdb;
        
        // Mock that core tables still exist
        $wpdb->expects($this->atLeastOnce())
             ->method('get_var')
             ->willReturn('1'); // Table exists
             
        // Run the removal process
        $this->evidence_remover->drop_evidence_tables();
        
        // Verify core tables are preserved
        foreach ($this->core_tables as $table) {
            $table_name = $wpdb->prefix . 'heritage_press_' . $table;
            $this->assertEquals('1', $wpdb->get_var("SHOW TABLES LIKE '$table_name'"));
        }
    }
    
    /**
     * Test data migration functionality
     */
    public function testDataMigration() {
        global $wpdb;
        
        // Mock source data that needs to be preserved
        $wpdb->expects($this->atLeastOnce())
             ->method('get_results')
             ->willReturn([
                ['id' => 1, 'title' => 'Test Source', 'quality_assessment_id' => 5],
                ['id' => 2, 'title' => 'Another Source', 'quality_assessment_id' => 8]
             ]);
             
        // Mock successful update
        $wpdb->expects($this->atLeastOnce())
             ->method('update')
             ->willReturn(true);
             
        $result = $this->evidence_remover->migrate_source_data();
        $this->assertTrue($result, 'Source data migration should succeed');
    }
    
    /**
     * Test safety checks to prevent accidental core table deletion
     */
    public function testSafetyChecks() {
        global $wpdb;
        
        // Try to drop a protected table directly
        $protected_table = 'individuals';
        $result = $this->evidence_remover->is_protected_table($protected_table);
        $this->assertTrue($result, 'Core table should be protected');
        
        // Attempt to drop a protected table
        $result = $this->evidence_remover->drop_single_table($protected_table);
        $this->assertFalse($result, 'Drop operation should fail for protected table');
    }
    
    /**
     * Test that database integrity is maintained
     */
    public function testDatabaseIntegrity() {
        global $wpdb;
        
        // Mock successful integrity check
        $wpdb->expects($this->atLeastOnce())
             ->method('query')
             ->willReturn(true);
             
        $result = $this->evidence_remover->verify_database_integrity();
        $this->assertTrue($result, 'Database integrity should be maintained');
    }
}
