<?php
/**
 * Family Repository Test Class
 *
 * @package HeritagePress\Tests\Integration\Repository
 */

namespace HeritagePress\Tests\Integration\Repository;

use HeritagePress\Repositories\Family_Repository;
use HeritagePress\Models\Family_Model;
use HeritagePress\Core\Audit_Log_Observer;
use HeritagePress\Tests\HeritageTestCase;
use HeritagePress\Database\Database_Manager;

class FamilyRepositoryTest extends HeritageTestCase {

    private Family_Repository $repository;
    private Audit_Log_Observer $audit_observer;
    private static string $families_table_name;
    private static string $audit_logs_table_name;

    public static function setUpBeforeClass(): void {        parent::setUpBeforeClass();
        global $wpdb;
        self::$families_table_name = Database_Manager::get_table_prefix() . 'families';
        self::$audit_logs_table_name = Database_Manager::get_table_prefix() . 'audit_logs';

        // Register families table with all required columns including soft delete
        self::registerTable(self::$families_table_name, [
            'id', 'uuid', 'file_id', 'husband_id', 'wife_id', 'marriage_date', 'marriage_place_id',
            'divorce_date', 'divorce_place_id', 'user_reference_text', 'restriction_type', 'notes',
            'shared_note_id', 'privacy', 'status', 'created_at', 'updated_at', 'deleted_at'
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

        // Instantiate Family_Repository with the observer
        $this->repository = new Family_Repository($this->audit_observer);

        // Truncate tables before each test
        $this->truncateTables([self::$families_table_name, self::$audit_logs_table_name]);
    }    public function testCreateFamily() {
        global $wpdb;
        $data = [
            'uuid' => 'fam-uuid-create-123',
            'file_id' => 'file-abc',
            'husband_id' => 1,
            'wife_id' => 2,
            'status' => 'active',
        ];        $family_model = $this->repository->create($data);
        
        $this->assertInstanceOf(Family_Model::class, $family_model);
        $this->assertIsNumeric($family_model->id);
        $this->assertGreaterThan(0, $family_model->id);

        $created_family = $this->repository->get_by_id($family_model->id);
        $this->assertInstanceOf(Family_Model::class, $created_family);
        $this->assertEquals('fam-uuid-create-123', $created_family->uuid);
        $this->assertEquals(1, $created_family->husband_id);

        // Assert audit log entry
        $log_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$audit_logs_table_name . " WHERE entity_id = %d AND action = 'CREATE' AND entity_table = %s",
            $family_model->id,
            'families'
        ));
        $this->assertNotNull($log_entry, "Audit log entry for 'CREATE' not found.");
        $this->assertEquals('CREATE', $log_entry->action);
        $this->assertEquals('families', $log_entry->entity_table);
        $this->assertEquals($family_model->id, $log_entry->entity_id);
        $this->assertEquals('fam-uuid-create-123', $log_entry->entity_uuid);
        $this->assertEquals('file-abc', $log_entry->file_id);
        $this->assertNotEmpty($log_entry->changed_fields);
        $changed_data = json_decode($log_entry->changed_fields, true);
        $this->assertEquals('fam-uuid-create-123', $changed_data['uuid']);
        $this->assertEquals(1, $changed_data['husband_id']);
    }

    public function testCreateFamilyMissingRequiredFields() {
        $data = [
            'husband_id' => 1,
        ]; // Missing uuid and file_id
        $result = $this->repository->create($data);
        $this->assertFalse($result);
    }

    public function testGetFamilyById() {
        $data = [
            'uuid' => 'fam-uuid-getid-456',
            'file_id' => 'file-def',
            'wife_id' => 3,
        ];
        $created_model = $this->repository->create($data);
        $this->assertInstanceOf(Family_Model::class, $created_model);
        
        $family = $this->repository->get_by_id($created_model->id);
        $this->assertInstanceOf(Family_Model::class, $family);
        $this->assertEquals($created_model->id, $family->id);
        $this->assertEquals(3, $family->wife_id);
    }

    public function testGetFamilyByUuid() {
        $uuid = 'fam-uuid-getuuid-789';
        $data = [
            'uuid' => $uuid,
            'file_id' => 'file-ghi',
            'husband_id' => 4,
        ];
        $created_model = $this->repository->create($data);
        $this->assertInstanceOf(Family_Model::class, $created_model);

        $family = $this->repository->get_by_uuid($uuid);
        $this->assertInstanceOf(Family_Model::class, $family);
        $this->assertEquals($uuid, $family->uuid);
        $this->assertEquals(4, $family->husband_id);
    }

    public function testUpdateFamily() {
        global $wpdb;
        $initial_data = [
            'uuid' => 'fam-uuid-update-111',
            'file_id' => 'file-jkl',
            'husband_id' => 5,
            'wife_id' => 6,
            'marriage_date' => '2000-01-01',
            'status' => 'active',
        ];
        $family_model = $this->repository->create($initial_data);
        $this->assertInstanceOf(Family_Model::class, $family_model);
        $id = $family_model->id;

        // Clear audit logs after creation to isolate update log
        $this->truncateTables([self::$audit_logs_table_name]);        $update_data = [
            'husband_id' => 7,
            'marriage_date' => '2001-02-02',
            'notes' => 'Updated notes.'
        ];
        
        // Advance time to ensure updated_at changes
        \HeritagePress\Tests\Mocks\MockWP::advance_time(1);
        
        $result = $this->repository->update($id, $update_data);
        $this->assertTrue($result);

        $updated_family = $this->repository->get_by_id($id);
        $this->assertInstanceOf(Family_Model::class, $updated_family);
        $this->assertEquals(7, $updated_family->husband_id);
        $this->assertEquals('2001-02-02', $updated_family->marriage_date);
        $this->assertEquals('Updated notes.', $updated_family->notes);

        // Assert audit log entry for update
        $log_entry_update = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$audit_logs_table_name . " WHERE entity_id = %d AND action = 'UPDATE' AND entity_table = %s ORDER BY id DESC LIMIT 1",
            $id,
            'families'
        ));
        $this->assertNotNull($log_entry_update, "Audit log entry for 'UPDATE' not found.");
        $this->assertEquals('UPDATE', $log_entry_update->action);
        $this->assertEquals($id, $log_entry_update->entity_id);
        $this->assertEquals('fam-uuid-update-111', $log_entry_update->entity_uuid);
        $this->assertNotEmpty($log_entry_update->changed_fields);        $changed_fields_diff = json_decode($log_entry_update->changed_fields, true);
        $this->assertIsArray($changed_fields_diff, 'changed_fields should be a JSON array (diff object)');

        $this->assertArrayHasKey('husband_id', $changed_fields_diff);
        $this->assertEquals(5, $changed_fields_diff['husband_id']['old']);
        $this->assertEquals(7, $changed_fields_diff['husband_id']['new']);

        $this->assertArrayHasKey('marriage_date', $changed_fields_diff);
        $this->assertEquals('2000-01-01', $changed_fields_diff['marriage_date']['old']);
        $this->assertEquals('2001-02-02', $changed_fields_diff['marriage_date']['new']);
        
        $this->assertArrayHasKey('notes', $changed_fields_diff);
        $this->assertNull($changed_fields_diff['notes']['old']); // Notes was not in initial_data
        $this->assertEquals('Updated notes.', $changed_fields_diff['notes']['new']);

        $this->assertArrayHasKey('updated_at', $changed_fields_diff);
        $this->assertNotNull($changed_fields_diff['updated_at']['old']); // updated_at is set on create
        $this->assertNotNull($changed_fields_diff['updated_at']['new']);
        $this->assertNotEquals($changed_fields_diff['updated_at']['old'], $changed_fields_diff['updated_at']['new']);
    }
    
    // Test for attempting to update a non-existent family
    public function testUpdateNonExistentFamily() {
        $updateData = ['notes' => 'Attempt to update non-existent.'];
        $result = $this->repository->update(99999, $updateData); // Non-existent ID
        $this->assertFalse($result);
    }

    public function testDeleteFamily() { // Soft Delete
        global $wpdb;
        $data = [
            'uuid' => 'fam-uuid-delete-222',
            'file_id' => 'file-mno',
            'husband_id' => 8,
        ];
        $family_model = $this->repository->create($data);
        $this->assertInstanceOf(Family_Model::class, $family_model);
        $id = $family_model->id;

        $result = $this->repository->delete($id); // Soft delete
        $this->assertTrue($result);

        $deleted_family = $this->repository->get_by_id($id);
        $this->assertNull($deleted_family, 'Family should be soft-deleted and not fetched by default get_by_id.');

        // Verify it's in the database with deleted_at set
        $row = $wpdb->get_row($wpdb->prepare("SELECT deleted_at FROM " . self::$families_table_name . " WHERE id = %d", $id));
        $this->assertNotNull($row, 'Soft-deleted record should exist in DB.');
        $this->assertNotNull($row->deleted_at, 'deleted_at should be set for soft-deleted record.');

        // Assert audit log entry for soft delete
        $log_entry_delete = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$audit_logs_table_name . " WHERE entity_id = %d AND action = 'DELETE' AND entity_table = %s ORDER BY id DESC LIMIT 1",
            $id,
            'families'
        ));
        $this->assertNotNull($log_entry_delete, "Audit log entry for 'DELETE' not found.");
        $this->assertEquals('DELETE', $log_entry_delete->action);
        $this->assertEquals($id, $log_entry_delete->entity_id);
        $this->assertEquals('fam-uuid-delete-222', $log_entry_delete->entity_uuid);
    }
    
    // Test soft deleting a non-existent family
    public function testDeleteNonExistentFamily() {
        $result = $this->repository->delete(99999); // Non-existent ID
        $this->assertFalse($result, "Soft deleting a non-existent ID should return false.");
    }
    
    // Test soft deleting an already soft-deleted family
    public function testDeleteAlreadySoftDeletedFamily() {
        $data = ['uuid' => 'fam-already-deleted', 'file_id' => 'file-del'];
        $model = $this->repository->create($data);
        $this->repository->delete($model->id); // First delete
        $result = $this->repository->delete($model->id); // Second delete
        $this->assertTrue($result, "Attempting to soft-delete an already soft-deleted family should return true (idempotent).");
    }


    public function testRestoreFamily() {
        global $wpdb;
        $data = [
            'uuid' => 'fam-uuid-restore-333',
            'file_id' => 'file-pqr',
            'wife_id' => 9,
        ];
        $family_model = $this->repository->create($data);
        $this->assertInstanceOf(Family_Model::class, $family_model);
        $id = $family_model->id;

        $this->repository->delete($id); // Soft delete it first
        $this->assertNull($this->repository->get_by_id($id), 'Should be soft-deleted initially.');

        $result = $this->repository->restore($id);
        $this->assertTrue($result, 'Restore operation should succeed.');

        $restored_family = $this->repository->get_by_id($id);
        $this->assertInstanceOf(Family_Model::class, $restored_family);
        $this->assertEquals(9, $restored_family->wife_id);

        // Verify deleted_at is NULL in the database
        $row = $wpdb->get_row($wpdb->prepare("SELECT deleted_at FROM " . self::$families_table_name . " WHERE id = %d", $id));
        $this->assertNotNull($row);
        $this->assertNull($row->deleted_at, 'deleted_at should be NULL for a restored record.');

        // Assert audit log entry for restore
        $log_entry_restore = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$audit_logs_table_name . " WHERE entity_id = %d AND action = 'RESTORE' AND entity_table = %s ORDER BY id DESC LIMIT 1",
            $id,
            'families'
        ));
        $this->assertNotNull($log_entry_restore, "Audit log entry for 'RESTORE' not found.");
        $this->assertEquals('RESTORE', $log_entry_restore->action);
        $this->assertEquals($id, $log_entry_restore->entity_id);
        $this->assertEquals('fam-uuid-restore-333', $log_entry_restore->entity_uuid);
    }

    // Test restoring a non-existent family
    public function testRestoreNonExistentFamily() {
        $result = $this->repository->restore(99999);
        $this->assertFalse($result);
    }

    // Test restoring a family that was not soft-deleted
    public function testRestoreNonDeletedFamily() {
        $data = ['uuid' => 'fam-not-deleted', 'file_id' => 'file-nd'];
        $model = $this->repository->create($data);
        $result = $this->repository->restore($model->id);
        $this->assertTrue($result, "Restoring a non-deleted family should return true (idempotent).");
        $this->assertNotNull($this->repository->get_by_id($model->id)); // Still exists
    }

    public function testForceDeleteFamily() {
        global $wpdb;
        $data = [
            'uuid' => 'fam-uuid-force-delete-444',
            'file_id' => 'file-stu',
            'husband_id' => 10,
        ];
        $family_model = $this->repository->create($data);
        $this->assertInstanceOf(Family_Model::class, $family_model);
        $id = $family_model->id;

        $result = $this->repository->force_delete($id);
        $this->assertTrue($result, 'Force delete operation should succeed.');
        
        // Assert audit log entry for force_deleting
        $log_entry_force_delete = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$audit_logs_table_name . " WHERE entity_id = %d AND action = 'FORCE_DELETE' AND entity_table = %s ORDER BY id DESC LIMIT 1",
            $id, 
            'families'
        ));
        $this->assertNotNull($log_entry_force_delete, "Audit log entry for 'FORCE_DELETE' not found.");
        $this->assertEquals('FORCE_DELETE', $log_entry_force_delete->action);
        $this->assertEquals($id, $log_entry_force_delete->entity_id);
        $this->assertEquals('fam-uuid-force-delete-444', $log_entry_force_delete->entity_uuid);

        // Should not exist in families database at all
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::$families_table_name . " WHERE id = %d", $id));
        $this->assertNull($row, 'Family should be permanently deleted from the database.');
    }

    // Test force deleting a non-existent family
    public function testForceDeleteNonExistentFamily() {
        $result = $this->repository->force_delete(99999);
        $this->assertFalse($result);
    }

    public function testGetAllFamilies() {
        $data1 = ['uuid' => 'fam-getall-uuid-1', 'file_id' => 'file-1', 'husband_id' => 11];
        $data2 = ['uuid' => 'fam-getall-uuid-2', 'file_id' => 'file-1', 'wife_id' => 12];
        $data3 = ['uuid' => 'fam-getall-uuid-3', 'file_id' => 'file-1', 'husband_id' => 13];

        $model1 = $this->repository->create($data1);
        $model2 = $this->repository->create($data2);
        $this->repository->create($data3);
        $this->assertInstanceOf(Family_Model::class, $model1);
        $this->assertInstanceOf(Family_Model::class, $model2);


        $this->repository->delete($model2->id); // Soft delete one of them

        // Test get_all without deleted records
        $families = $this->repository->get_all();
        $this->assertCount(2, $families, 'Should retrieve 2 non-deleted families.');
        foreach ($families as $fam) {
            $this->assertInstanceOf(Family_Model::class, $fam);
        }
        $retrieved_ids = array_map(fn($fam) => $fam->id, $families);
        $this->assertContains($model1->id, $retrieved_ids);
        $this->assertNotContains($model2->id, $retrieved_ids);

        // Test get_all with deleted records
        $all_families = $this->repository->get_all(true);
        $this->assertCount(3, $all_families, 'Should retrieve all 3 families, including soft-deleted.');
        foreach ($all_families as $fam) {
            $this->assertInstanceOf(Family_Model::class, $fam);
        }
        $all_retrieved_ids = array_map(fn($fam) => $fam->id, $all_families);
        $this->assertContains($model1->id, $all_retrieved_ids);
        $this->assertContains($model2->id, $all_retrieved_ids);
    }

    public function testGetByIdReturnsNullForSoftDeleted() {
        $data = ['uuid' => 'fam-getbyid-softdelete-uuid', 'file_id' => 'file-soft', 'husband_id' => 14];
        $model = $this->repository->create($data);
        $this->assertInstanceOf(Family_Model::class, $model);
        $this->repository->delete($model->id); 

        $family = $this->repository->get_by_id($model->id);
        $this->assertNull($family, 'get_by_id should return null for a soft-deleted family.');
    }
    
    public function testGetByIdWithTrashed() {
        $data = ['uuid' => 'fam-getbyid-trashed-uuid', 'file_id' => 'file-soft', 'husband_id' => 16];
        $model = $this->repository->create($data);
        $this->assertInstanceOf(Family_Model::class, $model);
        $this->repository->delete($model->id); 

        $family = $this->repository->get_by_id($model->id, true); // Get with trashed
        $this->assertInstanceOf(Family_Model::class, $family);
        $this->assertEquals($model->id, $family->id);
        $this->assertNotNull($family->deleted_at);
    }

    public function testGetByUuidReturnsNullForSoftDeleted() {
        $uuid = 'fam-getbyuuid-softdelete-uuid';
        $data = ['uuid' => $uuid, 'file_id' => 'file-soft-uuid', 'wife_id' => 15];
        $model = $this->repository->create($data);
        $this->assertInstanceOf(Family_Model::class, $model);
        $this->repository->delete($model->id);

        $family = $this->repository->get_by_uuid($uuid);
        $this->assertNull($family, 'get_by_uuid should return null for a soft-deleted family.');
    }

    public function testGetByUuidWithTrashed() {
        $uuid = 'fam-getbyuuid-trashed-uuid';
        $data = ['uuid' => $uuid, 'file_id' => 'file-soft-uuid', 'wife_id' => 17];
        $model = $this->repository->create($data);
        $this->assertInstanceOf(Family_Model::class, $model);
        $this->repository->delete($model->id);

        $family = $this->repository->get_by_uuid($uuid, true); // Get with trashed
        $this->assertInstanceOf(Family_Model::class, $family);
        $this->assertEquals($uuid, $family->uuid);
        $this->assertNotNull($family->deleted_at);
    }

    // ... (tearDown and tearDownAfterClass remain the same)
    protected function tearDown(): void {
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void {
        parent::tearDownAfterClass();
    }
}
