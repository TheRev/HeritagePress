<?php
/**
 * Individual Repository Test Class
 *
 * @package HeritagePress\\Tests\\Integration\\Repository
 */

namespace HeritagePress\\Tests\\Integration\\Repository;

use HeritagePress\\Repositories\\Individual_Repository;
use HeritagePress\\Core\\Audit_Log_Observer;
use HeritagePress\\Tests\\HeritageTestCase;
use HeritagePress\\Database\\Database_Manager;
use HeritagePress\\Models\\Individual_Model; // Ensuring this use statement is correctly placed and formatted.

class IndividualRepositoryTest extends HeritageTestCase {

    private $repository;
    private $audit_observer; // Added
    private static $individuals_table_name; // Renamed for clarity
    private static $audit_logs_table_name; // Added

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $wpdb;
        self::$individuals_table_name = Database_Manager::get_table_prefix() . 'individuals';
        self::$audit_logs_table_name = Database_Manager::get_table_prefix() . 'audit_logs'; // Added

        self::registerTable(self::$individuals_table_name, [
            'id', 'uuid', 'file_id', 'given_names', 'surname', 'sex', 'birth_date', 'status', 'restriction_type', 'deleted_at'
        ]);
        // Added audit_logs table registration
        self::registerTable(self::$audit_logs_table_name, [
            'id', 'user_id', 'action', 'entity_table', 'entity_id', 'entity_uuid', 'file_id', 'changed_fields', 'ip_address', 'timestamp'
        ]);
    }

    protected function setUp(): void {
        parent::setUp();
        global $wpdb; // Ensure $wpdb is available

        // Instantiate Audit_Log_Observer
        // The observer expects $wpdb and the audit table name.
        // In a test environment, $wpdb is typically the mocked/test instance.
        $this->audit_observer = new Audit_Log_Observer($wpdb, self::$audit_logs_table_name);

        // Instantiate Individual_Repository with the observer
        $this->repository = new Individual_Repository($this->audit_observer);

        // Truncate tables before each test
        $this->truncateTables([self::$individuals_table_name, self::$audit_logs_table_name]); // Added audit_logs table
    }

    public function testCreateIndividual() {
        global $wpdb; // Ensure $wpdb is available for direct queries
        $data = [
            'uuid' => 'test-uuid-123',
            'file_id' => 'file-abc',
            'given_names' => 'John',
            'surname' => 'Doe',
            'sex' => 'M',
            'status' => 'active',
        ];
        $id = $this->repository->create($data);
        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, $id);

        $createdIndividual = $this->repository->get_by_id($id); // Returns Individual_Model or null
        $this->assertNotNull($createdIndividual);
        $this->assertInstanceOf(Individual_Model::class, $createdIndividual); // Added: Assert model type
        $this->assertEquals('test-uuid-123', $createdIndividual->uuid);
        $this->assertEquals('John', $createdIndividual->given_names);

        // Assert audit log entry
        $log_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$audit_logs_table_name . " WHERE entity_id = %d AND action = 'created' AND entity_table = %s",
            $id,
            'individuals'
        ));
        $this->assertNotNull($log_entry, "Audit log entry for 'created' not found.");
        $this->assertEquals('created', $log_entry->action);
        $this->assertEquals('individuals', $log_entry->entity_table);
        $this->assertEquals($id, $log_entry->entity_id);
        $this->assertEquals('test-uuid-123', $log_entry->entity_uuid);
        $this->assertEquals('file-abc', $log_entry->file_id);
        $this->assertNotEmpty($log_entry->changed_fields);
        $changed_data = json_decode($log_entry->changed_fields, true);
        $this->assertEquals('test-uuid-123', $changed_data['uuid']);
        $this->assertEquals('John', $changed_data['given_names']);
    }

    public function testCreateIndividualMissingRequiredFields() {
        $data = [
            'given_names' => 'Jane',
            'surname' => 'Doe',
        ];
        $result = $this->repository->create($data);
        $this->assertFalse($result);
    }

    public function testGetIndividualById() {
        $data = [
            'uuid' => 'test-uuid-456',
            'file_id' => 'file-def',
            'given_names' => 'Peter',
            'surname' => 'Pan',
        ];
        $id = $this->repository->create($data);
        $individual = $this->repository->get_by_id($id); // Returns Individual_Model or null
        $this->assertNotNull($individual);
        $this->assertInstanceOf(Individual_Model::class, $individual); // Added: Assert model type
        $this->assertEquals($id, $individual->id);
        $this->assertEquals('Peter', $individual->given_names);
    }

    public function testGetIndividualByUuid() {
        $data = [
            'uuid' => 'test-uuid-789',
            'file_id' => 'file-ghi',
            'given_names' => 'Alice',
            'surname' => 'Wonderland',
        ];
        $this->repository->create($data);
        $individual = $this->repository->get_by_uuid('test-uuid-789'); // Returns Individual_Model or null
        $this->assertNotNull($individual);
        $this->assertInstanceOf(Individual_Model::class, $individual); // Added: Assert model type
        $this->assertEquals('test-uuid-789', $individual->uuid);
        $this->assertEquals('Alice', $individual->given_names);
    }

    public function testUpdateIndividual() {
        global $wpdb; // Ensure $wpdb is available
        $initialData = [
            'uuid' => 'update-uuid-111',
            'file_id' => 'file-jkl',
            'given_names' => 'Initial',
            'surname' => 'Name',
            'sex' => 'F',
            'status' => 'active', // Added for completeness from create defaults
        ];
        $id = $this->repository->create($initialData);
        $this->assertIsNumeric($id);

        // Clear audit logs after creation to isolate update log
        $this->truncateTables([self::$audit_logs_table_name]);

        $updateData = [
            'given_names' => 'Updated',
            'surname' => 'NameAgain',
            'sex' => 'U',
            'restriction_type' => 'CONFIDENTIAL,LOCKED'
        ];
        $result = $this->repository->update($id, $updateData);
        $this->assertTrue($result);

        $updatedIndividual = $this->repository->get_by_id($id); // Returns Individual_Model or null
        $this->assertNotNull($updatedIndividual);
        $this->assertInstanceOf(Individual_Model::class, $updatedIndividual); // Added: Assert model type
        $this->assertEquals('Updated', $updatedIndividual->given_names);
        $this->assertEquals('NameAgain', $updatedIndividual->surname);
        $this->assertEquals('U', $updatedIndividual->sex);
        $this->assertEquals('CONFIDENTIAL,LOCKED', $updatedIndividual->restriction_type);

        // Assert audit log entry for update
        $log_entry_update = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$audit_logs_table_name . " WHERE entity_id = %d AND action = 'updated' AND entity_table = %s ORDER BY id DESC LIMIT 1",
            $id,
            'individuals'
        ));
        $this->assertNotNull($log_entry_update, "Audit log entry for 'updated' not found.");
        $this->assertEquals('updated', $log_entry_update->action);
        $this->assertEquals($id, $log_entry_update->entity_id);
        $this->assertEquals('update-uuid-111', $log_entry_update->entity_uuid);
        $this->assertNotEmpty($log_entry_update->changed_fields);
        
        $changed_fields_diff = json_decode($log_entry_update->changed_fields, true);
        $this->assertIsArray($changed_fields_diff, 'changed_fields should be a JSON array (diff object)');

        // Check specific changed fields for old and new values
        $this->assertArrayHasKey('given_names', $changed_fields_diff);
        $this->assertEquals('Initial', $changed_fields_diff['given_names']['old']);
        $this->assertEquals('Updated', $changed_fields_diff['given_names']['new']);

        $this->assertArrayHasKey('surname', $changed_fields_diff);
        $this->assertEquals('Name', $changed_fields_diff['surname']['old']);
        $this->assertEquals('NameAgain', $changed_fields_diff['surname']['new']);

        $this->assertArrayHasKey('sex', $changed_fields_diff);
        $this->assertEquals('F', $changed_fields_diff['sex']['old']);
        $this->assertEquals('U', $changed_fields_diff['sex']['new']);

        $this->assertArrayHasKey('restriction_type', $changed_fields_diff);
        $this->assertNull($changed_fields_diff['restriction_type']['old']); // Was not set in initialData, so defaults to null
        $this->assertEquals('CONFIDENTIAL,LOCKED', $changed_fields_diff['restriction_type']['new']);
        
        // Check that 'updated_at' is also captured in the diff
        $this->assertArrayHasKey('updated_at', $changed_fields_diff);
        $this->assertNull($changed_fields_diff['updated_at']['old']); // Assuming updated_at is not set on create or is null
        $this->assertNotNull($changed_fields_diff['updated_at']['new']);
    }
    
    public function testUpdateIndividualInvalidSex() {
        $initialData = [
            'uuid' => 'update-uuid-sex',
            'file_id' => 'file-sex',
            'given_names' => 'Test',
            'surname' => 'SexValidation',
            'sex' => 'M',
        ];
        $id = $this->repository->create($initialData);
        $this->assertIsNumeric($id);

        $updateData = [
            'sex' => 'INVALID_SEX_VALUE',
        ];
        $this->repository->update($id, $updateData); // The method should handle this by unsetting or defaulting
        $updatedIndividual = $this->repository->get_by_id($id);
        $this->assertEquals('M', $updatedIndividual->sex); // Assuming it unsets invalid and keeps original
    }

    public function testDeleteIndividual() {
        global $wpdb; // Ensure $wpdb is available
        $data = [
            'uuid' => 'delete-uuid-222',
            'file_id' => 'file-mno',
            'given_names' => 'ToDelete',
            'surname' => 'Person',
        ];
        $id = $this->repository->create($data);
        $this->assertIsNumeric($id);

        // Soft delete
        $result = $this->repository->delete($id);
        $this->assertTrue($result);

        // Should not be retrievable by default get_by_id
        $deletedIndividual = $this->repository->get_by_id($id);
        $this->assertNull($deletedIndividual, 'Individual should be soft-deleted and not fetched by get_by_id.');

        // Verify it's in the database with deleted_at set (requires direct DB check or a specific getter)
        $row = $wpdb->get_row($wpdb->prepare("SELECT deleted_at FROM " . self::$individuals_table_name . " WHERE id = %d", $id));
        $this->assertNotNull($row, 'Soft-deleted record should exist in DB.');
        $this->assertNotNull($row->deleted_at, 'deleted_at should be set for soft-deleted record.');

        // Assert audit log entry for soft delete
        $log_entry_delete = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$audit_logs_table_name . " WHERE entity_id = %d AND action = 'deleting' AND entity_table = %s ORDER BY id DESC LIMIT 1",
            $id,
            'individuals'
        ));
        $this->assertNotNull($log_entry_delete, "Audit log entry for 'deleting' not found.");
        $this->assertEquals('deleting', $log_entry_delete->action);
        $this->assertEquals($id, $log_entry_delete->entity_id);
        $this->assertEquals('delete-uuid-222', $log_entry_delete->entity_uuid); // Assuming uuid is logged
    }

    public function testDeleteNonExistentIndividual() {
        $result = $this->repository->delete(99999); // Non-existent ID
        // $wpdb->update returns 0 if no rows are updated, which our repository method treats as success (true)
        // This behavior might be debatable (should it be false if no row found to update?)
        // For now, testing current behavior where it returns true if $wpdb->update doesn't return false.
        $this->assertTrue($result, "Soft deleting a non-existent ID should not cause a wpdb error and return true.");
    }

    public function testRestoreIndividual() {
        global $wpdb; // Ensure $wpdb is available
        $data = [
            'uuid' => 'restore-uuid-333',
            'file_id' => 'file-pqr',
            'given_names' => 'ToRestore',
            'surname' => 'Person',
        ];
        $id = $this->repository->create($data);
        $this->assertIsNumeric($id);

        // Soft delete it first
        $this->repository->delete($id);
        $this->assertNull($this->repository->get_by_id($id), 'Should be soft-deleted initially.');

        // Restore it
        $result = $this->repository->restore($id);
        $this->assertTrue($result, 'Restore operation should succeed.');

        $restoredIndividual = $this->repository->get_by_id($id); // Returns Individual_Model or null
        $this->assertNotNull($restoredIndividual, 'Individual should be restored and retrievable.');
        $this->assertInstanceOf(Individual_Model::class, $restoredIndividual); // Added: Assert model type
        $this->assertEquals('ToRestore', $restoredIndividual->given_names);

        // Verify deleted_at is NULL in the database
        $row = $wpdb->get_row($wpdb->prepare("SELECT deleted_at FROM " . self::$individuals_table_name . " WHERE id = %d", $id));
        $this->assertNotNull($row);
        $this->assertNull($row->deleted_at, 'deleted_at should be NULL for a restored record.');

        // Assert audit log entry for restore
        $log_entry_restore = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$audit_logs_table_name . " WHERE entity_id = %d AND action = 'restored' AND entity_table = %s ORDER BY id DESC LIMIT 1",
            $id,
            'individuals'
        ));
        $this->assertNotNull($log_entry_restore, "Audit log entry for 'restored' not found.");
        $this->assertEquals('restored', $log_entry_restore->action);
        $this->assertEquals($id, $log_entry_restore->entity_id);
        $this->assertEquals('restore-uuid-333', $log_entry_restore->entity_uuid); // Assuming uuid is logged
    }

    public function testForceDeleteIndividual() {
        global $wpdb; // Ensure $wpdb is available
        $data = [
            'uuid' => 'force-delete-uuid-444',
            'file_id' => 'file-stu',
            'given_names' => 'ToForceDelete',
            'surname' => 'Person',
        ];
        $id = $this->repository->create($data);
        $this->assertIsNumeric($id);

        // Assert audit log entry for force delete (before actual deletion from individuals table)
        // The observer is called before the DB operation.
        // We need to capture the state *before* it's gone.
        // This means the log should exist even if the main record is then deleted.

        $result = $this->repository->force_delete($id);
        $this->assertTrue($result, 'Force delete operation should succeed.');
        
        // Assert audit log entry for force_deleting
        $log_entry_force_delete = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$audit_logs_table_name . " WHERE entity_id = %d AND action = 'force_deleting' AND entity_table = %s ORDER BY id DESC LIMIT 1",
            $id, // The ID that was deleted
            'individuals'
        ));
        $this->assertNotNull($log_entry_force_delete, "Audit log entry for 'force_deleting' not found.");
        $this->assertEquals('force_deleting', $log_entry_force_delete->action);
        $this->assertEquals($id, $log_entry_force_delete->entity_id);
        $this->assertEquals('force-delete-uuid-444', $log_entry_force_delete->entity_uuid); // Assuming uuid is logged

        // Should not exist in individuals database at all
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::$individuals_table_name . " WHERE id = %d", $id));
        $this->assertNull($row, 'Individual should be permanently deleted from the database.');
    }

    public function testGetAllIndividuals() {
        $data1 = ['uuid' => 'getall-uuid-1', 'file_id' => 'file-1', 'given_names' => 'Alpha', 'surname' => 'One'];
        $data2 = ['uuid' => 'getall-uuid-2', 'file_id' => 'file-1', 'given_names' => 'Beta', 'surname' => 'Two'];
        $data3 = ['uuid' => 'getall-uuid-3', 'file_id' => 'file-1', 'given_names' => 'Gamma', 'surname' => 'Three'];

        $id1 = $this->repository->create($data1);
        $id2 = $this->repository->create($data2);
        $id3 = $this->repository->create($data3);

        // Soft delete one of them
        $this->repository->delete($id2);

        // Test get_all without deleted records
        $individuals = $this->repository->get_all(); // Returns Individual_Model[]
        $this->assertCount(2, $individuals, 'Should retrieve 2 non-deleted individuals.');
        foreach ($individuals as $ind) { // Added: Loop to assert model type for each item
            $this->assertInstanceOf(Individual_Model::class, $ind);
        }
        $retrieved_ids = array_map(function($ind) { return $ind->id; }, $individuals);
        $this->assertContains($id1, $retrieved_ids);
        $this->assertNotContains($id2, $retrieved_ids);
        $this->assertContains($id3, $retrieved_ids);

        // Test get_all with deleted records
        $all_individuals = $this->repository->get_all(true); // Returns Individual_Model[]
        $this->assertCount(3, $all_individuals, 'Should retrieve all 3 individuals, including soft-deleted.');
        foreach ($all_individuals as $ind) { // Added: Loop to assert model type for each item
            $this->assertInstanceOf(Individual_Model::class, $ind);
        }
        $all_retrieved_ids = array_map(function($ind) { return $ind->id; }, $all_individuals);
        $this->assertContains($id1, $all_retrieved_ids);
        $this->assertContains($id2, $all_retrieved_ids);
        $this->assertContains($id3, $all_retrieved_ids);
    }

    public function testGetByIdReturnsNullForSoftDeleted() {
        $data = [
            'uuid' => 'getbyid-softdelete-uuid',
            'file_id' => 'file-soft',
            'given_names' => 'Soft',
            'surname' => 'DeleteTest',
        ];
        $id = $this->repository->create($data);
        $this->assertIsNumeric($id);

        $this->repository->delete($id); // Soft delete

        $individual = $this->repository->get_by_id($id);
        $this->assertNull($individual, 'get_by_id should return null for a soft-deleted individual.');
    }

    public function testGetByUuidReturnsNullForSoftDeleted() {
        $uuid = 'getbyuuid-softdelete-uuid';
        $data = [
            'uuid' => $uuid,
            'file_id' => 'file-soft-uuid',
            'given_names' => 'SoftUuid',
            'surname' => 'DeleteTest',
        ];
        $id = $this->repository->create($data);
        $this->assertIsNumeric($id);

        $this->repository->delete($id); // Soft delete

        $individual = $this->repository->get_by_uuid($uuid);
        $this->assertNull($individual, 'get_by_uuid should return null for a soft-deleted individual.');
    }

    public function testFindBySurname() {
        $surname_to_find = 'Smith';
        $data1 = ['uuid' => 'find-uuid-1', 'file_id' => 'file-find', 'given_names' => 'John', 'surname' => $surname_to_find];
        $data2 = ['uuid' => 'find-uuid-2', 'file_id' => 'file-find', 'given_names' => 'Jane', 'surname' => $surname_to_find];
        $data3 = ['uuid' => 'find-uuid-3', 'file_id' => 'file-find', 'given_names' => 'Peter', 'surname' => 'Jones']; // Different surname
        $data4 = ['uuid' => 'find-uuid-4', 'file_id' => 'file-find', 'given_names' => 'James', 'surname' => $surname_to_find]; // To be soft-deleted

        $id1 = $this->repository->create($data1);
        $this->repository->create($data2);
        $this->repository->create($data3);
        $id4 = $this->repository->create($data4);

        // Soft delete one of the Smiths
        $this->repository->delete($id4);

        $results = $this->repository->find_by_surname($surname_to_find); // Returns Individual_Model[]
        $this->assertCount(2, $results, 'Should find 2 individuals with the surname Smith.');

        $found_names = [];
        foreach ($results as $individual) { // Added: Assert model type inside loop
            $this->assertInstanceOf(Individual_Model::class, $individual);
            $this->assertEquals($surname_to_find, $individual->surname);
            $found_names[] = $individual->given_names;
        }
        $this->assertContains('John', $found_names);
        $this->assertContains('Jane', $found_names);
        $this->assertNotContains('James', $found_names, 'Soft-deleted record should not be found.');
    }

    public function testFindBySurnameEmptyResult() {
        $results = $this->repository->find_by_surname('NonExistentSurname');
        $this->assertCount(0, $results);
    }

    public function testFindBySurnameEmptyInput() {
        $results = $this->repository->find_by_surname('');
        $this->assertCount(0, $results);
    }

    protected function tearDown(): void {
        // Clean up tables after each test if MockWPDB doesn't auto-reset or if using real DB
        // $this->truncateTables([self::$table_name]);
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void {
        parent::tearDownAfterClass();
        // Potentially clean up any static resources or database connections if needed
    }
}
