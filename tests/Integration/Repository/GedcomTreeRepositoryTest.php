<?php
/**
 * GedcomTree Repository Test Class
 *
 * @package HeritagePress\Tests\Integration\Repository
 */

namespace HeritagePress\Tests\Integration\Repository;

use HeritagePress\Repositories\GedcomTree_Repository;
use HeritagePress\Tests\HeritageTestCase;
use HeritagePress\Database\Database_Manager;

class GedcomTreeRepositoryTest extends HeritageTestCase {

    private $repository;
    private static $table_name;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::$table_name = Database_Manager::get_table_prefix() . 'gedcom_trees';
        self::registerTable(self::$table_name, [
            'id', 'tree_id', 'file_name', 'title', 'status', 'gedcom_version' // Add other relevant columns
        ]);
    }

    protected function setUp(): void {
        parent::setUp();
        $this->repository = new GedcomTree_Repository();
        $this->truncateTables([self::$table_name]);
    }

    public function testCreateTree() {
        $data = [
            'tree_id' => 'tree-uuid-001',
            'file_name' => 'my_family_tree.ged',
            'title' => 'My Awesome Family Tree',
            'gedcom_version' => '7.0',
            'status' => 'active',
        ];
        $id = $this->repository->create($data);
        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, $id);

        $createdTree = $this->repository->get_by_id($id);
        $this->assertNotNull($createdTree);
        $this->assertEquals('tree-uuid-001', $createdTree->tree_id);
        $this->assertEquals('my_family_tree.ged', $createdTree->file_name);
        $this->assertEquals('My Awesome Family Tree', $createdTree->title);
    }

    public function testCreateTreeMissingRequiredFields() {
        $data = [
            'title' => 'Incomplete Tree',
        ];
        $result = $this->repository->create($data);
        $this->assertFalse($result, "Creation should fail if tree_id or file_name is missing.");

        $data_with_uuid = [
            'tree_id' => 'some-uuid',
        ];
        $result_uuid_only = $this->repository->create($data_with_uuid);
        $this->assertFalse($result_uuid_only, "Creation should fail if file_name is missing.");

        $data_with_filename = [
            'file_name' => 'some_file.ged',
        ];
        $result_filename_only = $this->repository->create($data_with_filename);
        $this->assertFalse($result_filename_only, "Creation should fail if tree_id is missing.");
    }

    public function testGetTreeById() {
        $data = [
            'tree_id' => 'tree-uuid-002',
            'file_name' => 'another_tree.ged',
            'title' => 'Another Tree',
        ];
        $id = $this->repository->create($data);
        $tree = $this->repository->get_by_id($id);
        $this->assertNotNull($tree);
        $this->assertEquals($id, $tree->id);
        $this->assertEquals('Another Tree', $tree->title);
    }

    public function testGetTreeByUuid() {
        $data = [
            'tree_id' => 'tree-uuid-003',
            'file_name' => 'yet_another_tree.ged',
            'title' => 'Yet Another Tree',
        ];
        $this->repository->create($data);
        $tree = $this->repository->get_by_uuid('tree-uuid-003');
        $this->assertNotNull($tree);
        $this->assertEquals('tree-uuid-003', $tree->tree_id);
        $this->assertEquals('Yet Another Tree', $tree->title);
    }

    public function testUpdateTree() {
        $initialData = [
            'tree_id' => 'update-tree-uuid-001',
            'file_name' => 'original_name.ged',
            'title' => 'Original Title',
            'status' => 'active',
        ];
        $id = $this->repository->create($initialData);
        $this->assertIsNumeric($id);

        $updateData = [
            'title' => 'Updated Title',
            'status' => 'archived',
            'description' => 'This tree has been updated.'
        ];
        $result = $this->repository->update($id, $updateData);
        $this->assertTrue($result);

        $updatedTree = $this->repository->get_by_id($id);
        $this->assertNotNull($updatedTree);
        $this->assertEquals('Updated Title', $updatedTree->title);
        $this->assertEquals('archived', $updatedTree->status);
        $this->assertEquals('This tree has been updated.', $updatedTree->description); // Assuming description column exists and is handled
    }

    public function testDeleteTree() {
        $data = [
            'tree_id' => 'delete-tree-uuid-001',
            'file_name' => 'to_be_deleted.ged',
            'title' => 'Tree for Deletion',
        ];
        $id = $this->repository->create($data);
        $this->assertIsNumeric($id);

        $result = $this->repository->delete($id);
        $this->assertTrue($result);

        $deletedTree = $this->repository->get_by_id($id);
        $this->assertNull($deletedTree);
    }

    public function testDeleteNonExistentTree() {
        $result = $this->repository->delete(99999); // Non-existent ID
        // $wpdb->delete returns number of rows affected. If 0 rows affected (no delete happened), it's 0.
        // If an error occurs, it's false. Our repository method returns true if $result is not false.
        // For a non-existent ID, $wpdb->delete returns 0, so our method returns true.
        // This might need adjustment if stricter "false if not found" is desired from repository delete.
        // For now, we test that it doesn't error out.
        $this->assertTrue($result, "Delete should not fail for non-existent ID, but affect 0 rows.");
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void {
        parent::tearDownAfterClass();
    }
}
