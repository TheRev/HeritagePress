<?php
/**
 * Family Relationship Test Class
 *
 * @package HeritagePress\Tests\Integration\Repository
 */

namespace HeritagePress\Tests\Integration\Repository;

use HeritagePress\Repositories\Family_Relationship_Repository;
use HeritagePress\Models\Family_Relationship_Model;
use HeritagePress\Core\Audit_Log_Observer;
use HeritagePress\Tests\HeritageTestCase;
use HeritagePress\Database\Database_Manager;

class FamilyRelationshipRepositoryTest extends HeritageTestCase {

    private Family_Relationship_Repository $repository;
    private Audit_Log_Observer $audit_observer;
    private static string $relationships_table_name;
    private static string $audit_logs_table_name;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $wpdb;
        self::$relationships_table_name = Database_Manager::get_table_prefix() . 'family_relationships';
        self::$audit_logs_table_name = Database_Manager::get_table_prefix() . 'audit_logs';

        // Register family_relationships table with all required columns including soft delete
        self::registerTable(self::$relationships_table_name, [
            'id', 'uuid', 'file_id', 'individual_id', 'family_id', 'relationship_type', 
            'pedigree_type', 'birth_order', 'is_current', 'notes', 'shared_note_id', 
            'privacy', 'status', 'created_at', 'updated_at', 'deleted_at'
        ]);

        // Register audit_logs table
        self::registerTable(self::$audit_logs_table_name, [
            'id', 'user_id', 'action', 'entity_table', 'entity_id', 'entity_uuid', 'file_id', 
            'changed_fields', 'ip_address', 'timestamp'
        ]);
    }

    protected function setUp(): void {
        parent::setUp();
        global $wpdb;

        // Instantiate Audit_Log_Observer
        $this->audit_observer = new Audit_Log_Observer($wpdb, self::$audit_logs_table_name);

        // Instantiate Family_Relationship_Repository with the observer
        $this->repository = new Family_Relationship_Repository($this->audit_observer);

        // Truncate tables before each test
        $this->truncateTables([self::$relationships_table_name, self::$audit_logs_table_name]);
    }

    public function testCreateRelationship() {
        $data = [
            'uuid' => 'relationship-uuid-001',
            'file_id' => 'file-001',
            'individual_id' => 123,
            'family_id' => 456,
            'relationship_type' => 'child',
            'pedigree_type' => 'birth'
        ];

        $relationship = $this->repository->create($data);
        
        $this->assertInstanceOf(Family_Relationship_Model::class, $relationship);
        $this->assertIsInt($relationship->id);
        $this->assertEquals('relationship-uuid-001', $relationship->uuid);
        $this->assertEquals(123, $relationship->individual_id);
        $this->assertEquals(456, $relationship->family_id);
        $this->assertEquals('child', $relationship->relationship_type);
        $this->assertEquals('birth', $relationship->pedigree_type);
        
        // Verify audit log entry was created
        global $wpdb;
        $audit_logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}heritage_press_audit_logs WHERE entity_table = 'family_relationships'"
        );
        
        $this->assertCount(1, $audit_logs);
        $this->assertEquals('creation', $audit_logs[0]->action);
    }

    public function testGetChildrenByFamily() {
        // Create parent-child relationships for a family
        $family_id = 100;
        $file_id = 'test-file-001';
        
        // Create 3 children relationships
        for ($i = 1; $i <= 3; $i++) {
            $this->repository->create([
                'uuid' => "child-rel-{$i}",
                'file_id' => $file_id,
                'individual_id' => 100 + $i, // Different IDs for each child
                'family_id' => $family_id,
                'relationship_type' => 'child',
                'pedigree_type' => 'birth',
                'birth_order' => $i
            ]);
        }
        
        // Create a child in a different family
        $this->repository->create([
            'uuid' => "child-rel-other-family",
            'file_id' => $file_id,
            'individual_id' => 200,
            'family_id' => 999, // Different family
            'relationship_type' => 'child',
            'pedigree_type' => 'birth'
        ]);
        
        // Test getting children for the family
        $children = $this->repository->get_children_by_family($family_id, $file_id);
        
        // Should return 3 children in birth order
        $this->assertCount(3, $children);
        $this->assertEquals(101, $children[0]->individual_id);
        $this->assertEquals(102, $children[1]->individual_id);
        $this->assertEquals(103, $children[2]->individual_id);
        
        // All should have relationship_type = 'child'
        foreach ($children as $child) {
            $this->assertEquals('child', $child->relationship_type);
        }
    }

    public function testGetParentsByFamily() {
        // Create parent relationships for a family
        $family_id = 200;
        $file_id = 'test-file-002';
        
        // Create husband relationship
        $this->repository->create([
            'uuid' => "husband-rel",
            'file_id' => $file_id,
            'individual_id' => 201,
            'family_id' => $family_id,
            'relationship_type' => 'husband'
        ]);
        
        // Create wife relationship
        $this->repository->create([
            'uuid' => "wife-rel",
            'file_id' => $file_id,
            'individual_id' => 202,
            'family_id' => $family_id,
            'relationship_type' => 'wife'
        ]);
        
        // Create a parent in a different family
        $this->repository->create([
            'uuid' => "parent-rel-other-family",
            'file_id' => $file_id,
            'individual_id' => 300,
            'family_id' => 999, // Different family
            'relationship_type' => 'husband'
        ]);
        
        // Test getting parents for the family
        $parents = $this->repository->get_parents_by_family($family_id, $file_id);
        
        // Should return 2 parents
        $this->assertCount(2, $parents);
        
        // Find husband and wife by their relationship types
        $husband = null;
        $wife = null;
        foreach ($parents as $parent) {
            if ($parent->relationship_type === 'husband') {
                $husband = $parent;
            } elseif ($parent->relationship_type === 'wife') {
                $wife = $parent;
            }
        }
        
        $this->assertNotNull($husband);
        $this->assertNotNull($wife);
        $this->assertEquals(201, $husband->individual_id);
        $this->assertEquals(202, $wife->individual_id);
    }

    public function testGetFamiliesAsChild() {
        // Create child relationships for an individual across multiple families
        $individual_id = 300;
        $file_id = 'test-file-003';
        
        // Create relationships in 2 families (could represent biological and adoptive families)
        $this->repository->create([
            'uuid' => "child-rel-1",
            'file_id' => $file_id,
            'individual_id' => $individual_id,
            'family_id' => 301,
            'relationship_type' => 'child',
            'pedigree_type' => 'birth'
        ]);
        
        $this->repository->create([
            'uuid' => "child-rel-2",
            'file_id' => $file_id,
            'individual_id' => $individual_id,
            'family_id' => 302,
            'relationship_type' => 'child',
            'pedigree_type' => 'adoption'
        ]);
        
        // Create a different individual's relationship
        $this->repository->create([
            'uuid' => "other-child-rel",
            'file_id' => $file_id,
            'individual_id' => 999,
            'family_id' => 301,
            'relationship_type' => 'child'
        ]);
        
        // Test getting families where this individual is a child
        $families = $this->repository->get_families_as_child($individual_id, $file_id);
        
        // Should return 2 family relationships
        $this->assertCount(2, $families);
        
        // Verify correct family IDs
        $found_family_ids = array_map(function($rel) {
            return $rel->family_id;
        }, $families);
        
        sort($found_family_ids);
        $this->assertEquals([301, 302], $found_family_ids);
        
        // All should have relationship_type = 'child'
        foreach ($families as $family_rel) {
            $this->assertEquals('child', $family_rel->relationship_type);
            $this->assertEquals($individual_id, $family_rel->individual_id);
        }
    }

    public function testGetFamiliesAsParent() {
        // Create parent relationships for an individual across multiple families
        $individual_id = 400;
        $file_id = 'test-file-004';
        
        // Create relationships in 2 families (could represent multiple marriages)
        $this->repository->create([
            'uuid' => "husband-rel-1",
            'file_id' => $file_id,
            'individual_id' => $individual_id,
            'family_id' => 401,
            'relationship_type' => 'husband',
            'is_current' => true
        ]);
        
        $this->repository->create([
            'uuid' => "husband-rel-2",
            'file_id' => $file_id,
            'individual_id' => $individual_id,
            'family_id' => 402,
            'relationship_type' => 'husband',
            'is_current' => false
        ]);
        
        // Create a different individual's relationship
        $this->repository->create([
            'uuid' => "other-husband-rel",
            'file_id' => $file_id,
            'individual_id' => 999,
            'family_id' => 403,
            'relationship_type' => 'husband'
        ]);
        
        // Test getting families where this individual is a parent
        $families = $this->repository->get_families_as_parent($individual_id, $file_id);
        
        // Should return 2 family relationships
        $this->assertCount(2, $families);
        
        // Verify correct family IDs
        $found_family_ids = array_map(function($rel) {
            return $rel->family_id;
        }, $families);
        
        sort($found_family_ids);
        $this->assertEquals([401, 402], $found_family_ids);
        
        // All should have individual_id = $individual_id
        foreach ($families as $family_rel) {
            $this->assertEquals($individual_id, $family_rel->individual_id);
            $this->assertEquals('husband', $family_rel->relationship_type);
        }
    }

    public function testUpdateRelationship() {
        // Create a relationship
        $data = [
            'uuid' => 'rel-to-update',
            'file_id' => 'file-005',
            'individual_id' => 500,
            'family_id' => 501,
            'relationship_type' => 'child',
            'pedigree_type' => 'birth',
            'birth_order' => 1
        ];
        
        $relationship = $this->repository->create($data);
        
        // Update the relationship
        $update_data = [
            'pedigree_type' => 'adoption',
            'birth_order' => 2,
            'notes' => 'Adoption finalized on 2020-01-01'
        ];
        
        $result = $this->repository->update($relationship->id, $update_data);
        $this->assertTrue($result);
        
        // Fetch the updated relationship
        $updated = $this->repository->get_by_id($relationship->id);
        
        // Verify updates were applied
        $this->assertEquals('adoption', $updated->pedigree_type);
        $this->assertEquals(2, $updated->birth_order);
        $this->assertEquals('Adoption finalized on 2020-01-01', $updated->notes);
        
        // Verify unchanged fields remain the same
        $this->assertEquals($relationship->uuid, $updated->uuid);
        $this->assertEquals($relationship->file_id, $updated->file_id);
        $this->assertEquals($relationship->individual_id, $updated->individual_id);
        $this->assertEquals($relationship->family_id, $updated->family_id);
        $this->assertEquals($relationship->relationship_type, $updated->relationship_type);
        
        // Verify audit log entry was created
        global $wpdb;
        $audit_logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}heritage_press_audit_logs 
             WHERE entity_table = 'family_relationships' AND action = 'update'"
        );
        
        $this->assertCount(1, $audit_logs);
        $this->assertEquals($relationship->uuid, $audit_logs[0]->entity_uuid);
    }

    public function testDeleteRelationship() {
        // Create a relationship
        $data = [
            'uuid' => 'rel-to-delete',
            'file_id' => 'file-006',
            'individual_id' => 600,
            'family_id' => 601,
            'relationship_type' => 'child'
        ];
        
        $relationship = $this->repository->create($data);
        
        // Delete the relationship (soft delete)
        $result = $this->repository->delete($relationship->id);
        $this->assertTrue($result);
        
        // Try to fetch the deleted relationship - should return null
        $deleted = $this->repository->get_by_id($relationship->id);
        $this->assertNull($deleted);
        
        // Verify audit log entry was created
        global $wpdb;
        $audit_logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}heritage_press_audit_logs 
             WHERE entity_table = 'family_relationships' AND action = 'deletion'"
        );
        
        $this->assertCount(1, $audit_logs);
        $this->assertEquals($relationship->uuid, $audit_logs[0]->entity_uuid);
        
        // Check that the record still exists in the database but has deleted_at set
        $db_record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}heritage_press_family_relationships WHERE id = %d",
                $relationship->id
            )
        );
        
        $this->assertNotNull($db_record);
        $this->assertNotNull($db_record->deleted_at);
    }
}
