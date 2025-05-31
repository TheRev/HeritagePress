<?php
/**
 * GEDCOM Family Relationships Test
 * Tests the GEDCOM parser's ability to extract and create family relationships from GEDCOM files
 * 
 * @package HeritagePress\Tests
 */

namespace HeritagePress\Tests\Integration;

use HeritagePress\Tests\HeritageTestCase;
use HeritagePress\GEDCOM\Gedcom7Parser;
use HeritagePress\GEDCOM\GedcomFamilyRelationshipHandler;

class GedcomFamilyRelationshipsTest extends HeritageTestCase {
    
    private $test_gedcom_path;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Create a temporary GEDCOM file for testing
        $this->test_gedcom_path = sys_get_temp_dir() . '/test_family_relationships.ged';
        
        // Sample GEDCOM file with family relationships
        $gedcom_content = "0 HEAD
1 CHAR UTF-8
1 GEDC
2 VERS 7.0
2 FORM LINEAGE-LINKED
0 @I1@ INDI
1 NAME John /Smith/
1 SEX M
1 BIRT
2 DATE 1 JAN 1970
1 FAMS @F1@
0 @I2@ INDI
1 NAME Mary /Johnson/
1 SEX F
1 BIRT
2 DATE 1 FEB 1972
1 FAMS @F1@
0 @I3@ INDI
1 NAME Michael /Smith/
1 SEX M
1 BIRT
2 DATE 15 MAR 1995
1 FAMC @F1@
0 @I4@ INDI
1 NAME Sarah /Smith/
1 SEX F
1 BIRT
2 DATE 20 APR 1997
1 FAMC @F1@
1 FAMC @F2@
2 PEDI adopted
0 @F1@ FAM
1 HUSB @I1@
1 WIFE @I2@
1 CHIL @I3@
1 CHIL @I4@
1 MARR
2 DATE 15 JUN 1994
0 @F2@ FAM
1 HUSB @I5@
1 WIFE @I6@
1 CHIL @I4@
0 @I5@ INDI
1 NAME Robert /Jones/
1 SEX M
1 FAMS @F2@
0 @I6@ INDI
1 NAME Patricia /Jones/
1 SEX F
1 FAMS @F2@
0 TRLR";
        
        file_put_contents($this->test_gedcom_path, $gedcom_content);
    }
    
    protected function tearDown(): void {
        // Remove the temporary GEDCOM file
        if (file_exists($this->test_gedcom_path)) {
            unlink($this->test_gedcom_path);
        }
        
        parent::tearDown();
    }
    
    public function testExtractFamilyRelationships() {
        $file_id = 'test-tree-' . uniqid();
        
        // Create a relationship handler
        $handler = new GedcomFamilyRelationshipHandler($file_id);
        
        // Process individuals
        $individual1 = [
            'data' => [
                ['tag' => 'NAME', 'value' => 'John /Smith/'],
                ['tag' => 'SEX', 'value' => 'M'],
                ['tag' => 'FAMS', 'value' => '@F1@']
            ]
        ];
        
        $individual2 = [
            'data' => [
                ['tag' => 'NAME', 'value' => 'Mary /Johnson/'],
                ['tag' => 'SEX', 'value' => 'F'],
                ['tag' => 'FAMS', 'value' => '@F1@']
            ]
        ];
        
        $individual3 = [
            'data' => [
                ['tag' => 'NAME', 'value' => 'Michael /Smith/'],
                ['tag' => 'SEX', 'value' => 'M'],
                ['tag' => 'FAMC', 'value' => '@F1@']
            ]
        ];
        
        // Process individuals with database IDs
        $handler->processIndividual($individual1['data'], 'I1', 101);
        $handler->processIndividual($individual2['data'], 'I2', 102);
        $handler->processIndividual($individual3['data'], 'I3', 103);
        
        // Process a family
        $handler->processFamily('F1', 201);
        
        // Check extracted relationships
        $relationships = $handler->getRelationships();
        
        // Should have 3 relationships (husband, wife, and child)
        $this->assertCount(3, $relationships);
        
        // Find each type of relationship
        $husband_rel = null;
        $wife_rel = null;
        $child_rel = null;
        
        foreach ($relationships as $rel) {
            if ($rel['individual_gedcom_id'] === 'I1') {
                $husband_rel = $rel;
            } elseif ($rel['individual_gedcom_id'] === 'I2') {
                $wife_rel = $rel;
            } elseif ($rel['individual_gedcom_id'] === 'I3') {
                $child_rel = $rel;
            }
        }
        
        // Verify relationships
        $this->assertNotNull($husband_rel);
        $this->assertEquals('husband', $husband_rel['relationship_type']);
        $this->assertEquals('F1', $husband_rel['family_gedcom_id']);
        
        $this->assertNotNull($wife_rel);
        $this->assertEquals('wife', $wife_rel['relationship_type']);
        $this->assertEquals('F1', $wife_rel['family_gedcom_id']);
        
        $this->assertNotNull($child_rel);
        $this->assertEquals('child', $child_rel['relationship_type']);
        $this->assertEquals('F1', $child_rel['family_gedcom_id']);
    }
    
    public function testPedigreeTypeExtraction() {
        $file_id = 'test-tree-' . uniqid();
        
        // Create a relationship handler
        $handler = new GedcomFamilyRelationshipHandler($file_id);
        
        // Process a child with adoption pedigree type
        $adopted_child = [
            'data' => [
                ['tag' => 'NAME', 'value' => 'Sarah /Smith/'],
                ['tag' => 'SEX', 'value' => 'F'],
                ['tag' => 'FAMC', 'value' => '@F2@', 'children' => [
                    ['tag' => 'PEDI', 'value' => 'adopted']
                ]]
            ]
        ];
        
        $handler->processIndividual($adopted_child['data'], 'I4', 104);
        $handler->processFamily('F2', 202);
        
        // Check extracted relationships
        $relationships = $handler->getRelationships();
        
        // Find the adoption relationship
        $adoption_rel = null;
        foreach ($relationships as $rel) {
            if ($rel['individual_gedcom_id'] === 'I4' && $rel['family_gedcom_id'] === 'F2') {
                $adoption_rel = $rel;
                break;
            }
        }
        
        // Verify relationship
        $this->assertNotNull($adoption_rel);
        $this->assertEquals('child', $adoption_rel['relationship_type']);
        $this->assertEquals('adopted', $adoption_rel['pedigree_type']);
    }
    
    /**
     * Integration test that parses a full GEDCOM file and ensures relationships are extracted correctly
     */
    public function testParseGedcomWithRelationships() {
        // This would be a more complex test that actually parses the full GEDCOM file
        // and verifies the relationship extraction through the entire pipeline
        
        // However, such a test would require setting up a full database environment and
        // mock repositories, which is beyond the scope of this example.
        
        // For now, we'll mark this as incomplete
        $this->markTestIncomplete(
            'This test would require a full database environment to test end-to-end GEDCOM parsing with relationships'
        );
    }
}
