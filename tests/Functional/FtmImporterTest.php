<?php
namespace HeritagePress\Tests;

use HeritagePress\Importers\FtmImporter;
use PHPUnit\Framework\TestCase;

class FtmImporterTest extends TestCase {
    protected $importer;
    protected $test_files_dir;

    public function setUp(): void {
        parent::setUp();
        $this->importer = new FtmImporter();
        $this->test_files_dir = dirname(__FILE__) . '/test-data/ftm/';

        // Create test directory if it doesn't exist
        if (!file_exists($this->test_files_dir)) {
            mkdir($this->test_files_dir, 0777, true);
        }
    }

    public function test_format_name() {
        $this->assertEquals('Family Tree Maker', $this->importer->get_format_name());
    }

    public function test_supported_extensions() {
        $extensions = $this->importer->get_supported_extensions();
        $this->assertContains('ftm', $extensions);
        $this->assertContains('ftmb', $extensions);
    }

    public function test_can_import_invalid_file() {
        $invalid_file = $this->test_files_dir . 'invalid.ftm';
        file_put_contents($invalid_file, 'Invalid content');
        
        $this->assertFalse($this->importer->can_import($invalid_file));
        unlink($invalid_file);
    }

    public function test_can_import_nonexistent_file() {
        $this->assertFalse($this->importer->can_import('nonexistent.ftm'));
    }

    public function test_can_import_ftm_file() {
        $valid_file = $this->test_files_dir . 'valid.ftm';
        $content = pack('C*', ...FTM_Importer::FTM_SIGNATURE);
        file_put_contents($valid_file, $content);
        
        $this->assertTrue($this->importer->can_import($valid_file));
        unlink($valid_file);
    }

    public function test_can_import_ftmb_file() {
        $valid_file = $this->test_files_dir . 'valid.ftmb';
        $content = pack('C*', ...FTM_Importer::FTMB_SIGNATURE);
        file_put_contents($valid_file, $content);
        
        $this->assertTrue($this->importer->can_import($valid_file));
        unlink($valid_file);
    }

    public function test_validate_invalid_file() {
        $invalid_file = $this->test_files_dir . 'invalid.ftm';
        file_put_contents($invalid_file, 'Invalid content');
        
        $result = $this->importer->validate($invalid_file);
        $this->assertNotEmpty($result['errors']);
        $this->assertContains('Invalid Family Tree Maker file format', $result['errors']);
        unlink($invalid_file);
    }

    public function test_import_invalid_file() {
        $invalid_file = $this->test_files_dir . 'invalid.ftm';
        file_put_contents($invalid_file, 'Invalid content');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Family Tree Maker file format');
        
        $this->importer->import($invalid_file);
        unlink($invalid_file);
    }

    // TODO: Add more tests when FTM file format parsing is implemented
}
