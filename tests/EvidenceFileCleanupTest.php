<?php
namespace HeritagePress\Tests;

/**
 * Test case for Evidence File Cleanup functionality
 *
 * Tests the identification and safe removal of Evidence Explained specific files
 * while preserving core files needed for genealogy functionality
 */
class EvidenceFileCleanupTest extends HeritagePressTestCase {
    protected $file_cleanup;
    protected $test_files;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Create mock file system for testing
        $this->createMockFileSystem();
        
        // Load the file cleanup class
        require_once HERITAGE_PRESS_PLUGIN_DIR . 'admin/tools/evidence-file-cleanup.php';
        $this->file_cleanup = new \Heritage_Press_File_Cleanup();
    }
    
    /**
     * Create a mock file system structure for testing
     */
    private function createMockFileSystem() {
        // Define test directory structure
        $this->test_files = [
            // Evidence files that should be identified for removal
            'includes/evidence/class-evidence-admin.php' => true,
            'includes/evidence/class-research-question.php' => true,
            'includes/evidence/class-proof-argument.php' => true,
            'includes/templates/evidence/evidence-editor.php' => true,
            'admin/evidence-manager.php' => true,
            
            // Core files that should be preserved
            'includes/core/class-plugin.php' => false,
            'includes/repositories/class-individual-repository.php' => false,
            'includes/gedcom/class-gedcom-parser.php' => false,
            'admin/tools/evidence-remover-menu.php' => false, // This is part of the removal tools
            'admin/tools/remove-evidence-system.php' => false  // This is part of the removal tools
        ];
        
        // Create a mock filesystem class for testing
        $this->file_system = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['file_exists'])
            ->getMock();
            
        // Set up the mock to return true for files in our test structure
        $this->file_system->method('file_exists')
            ->will($this->returnCallback(function($file) {
                return isset($this->test_files[$file]);
            }));
    }
    
    /**
     * Test identification of evidence files
     */
    public function testIdentifyEvidenceFiles() {
        // Create a method to scan for evidence files
        $evidence_files = $this->file_cleanup->identify_evidence_files();
        
        // Check that evidence files are identified
        $this->assertIsArray($evidence_files);
        $this->assertNotEmpty($evidence_files);
        
        // Check specific files
        $paths = array_column($evidence_files, 'path');
        $this->assertContains('includes/evidence/class-evidence-admin.php', $paths);
        $this->assertContains('includes/evidence/class-research-question.php', $paths);
        $this->assertContains('includes/evidence/class-proof-argument.php', $paths);
    }
    
    /**
     * Test that core files are excluded from removal
     */
    public function testCoreFilesNotIdentified() {
        $evidence_files = $this->file_cleanup->identify_evidence_files();
        
        // Create array of paths
        $paths = array_column($evidence_files, 'path');
        
        // Check that core files are not included
        $this->assertNotContains('includes/core/class-plugin.php', $paths);
        $this->assertNotContains('includes/repositories/class-individual-repository.php', $paths);
        $this->assertNotContains('includes/gedcom/class-gedcom-parser.php', $paths);
    }
    
    /**
     * Test creating backup before removal
     */
    public function testBackupBeforeRemoval() {
        // Mock the backup process
        $backup_file = $this->file_cleanup->create_backup([
            ['path' => 'includes/evidence/class-evidence-admin.php'],
            ['path' => 'includes/evidence/class-research-question.php']
        ]);
        
        $this->assertNotEmpty($backup_file);
        $this->assertStringContainsString('.zip', $backup_file);
    }
    
    /**
     * Test the safe file removal process
     */
    public function testSafeFileRemoval() {
        // Create a list of test files to remove
        $files_to_remove = [
            ['path' => 'includes/evidence/class-evidence-admin.php', 'status' => 'safe'],
            ['path' => 'includes/evidence/class-research-question.php', 'status' => 'safe']
        ];
        
        // Mock filesystem functions
        $this->file_cleanup = $this->getMockBuilder('\Heritage_Press_File_Cleanup')
            ->setMethods(['remove_file'])
            ->getMock();
        
        $this->file_cleanup->expects($this->exactly(2))
            ->method('remove_file')
            ->willReturn(true);
        
        // Test removal
        $result = $this->file_cleanup->remove_files($files_to_remove);
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['removed_count']);
    }
    
    /**
     * Test the cleanup report
     */
    public function testCleanupReport() {
        // Mock some removal actions
        $removal_results = [
            'success' => true,
            'removed_count' => 5,
            'failed_count' => 0,
            'backup_file' => 'heritage-press-evidence-files-backup-20250530.zip'
        ];
        
        $report = $this->file_cleanup->generate_report($removal_results);
        
        $this->assertNotEmpty($report);
        $this->assertStringContainsString('5 files', $report);
        $this->assertStringContainsString('backup', $report);
    }
    
    /**
     * Test handling of file removal failures
     */
    public function testHandleRemovalFailures() {
        // Mock the file removal process with some failures
        $this->file_cleanup = $this->getMockBuilder('\Heritage_Press_File_Cleanup')
            ->setMethods(['remove_file'])
            ->getMock();
        
        $this->file_cleanup->method('remove_file')
            ->will($this->returnCallback(function($file) {
                // Fail for one specific file
                if ($file === 'includes/evidence/class-proof-argument.php') {
                    return false;
                }
                return true;
            }));
        
        $files_to_remove = [
            ['path' => 'includes/evidence/class-evidence-admin.php', 'status' => 'safe'],
            ['path' => 'includes/evidence/class-research-question.php', 'status' => 'safe'],
            ['path' => 'includes/evidence/class-proof-argument.php', 'status' => 'safe']
        ];
        
        $result = $this->file_cleanup->remove_files($files_to_remove);
        
        $this->assertTrue($result['success']); // Overall process should succeed
        $this->assertEquals(2, $result['removed_count']);
        $this->assertEquals(1, $result['failed_count']);
    }
}
