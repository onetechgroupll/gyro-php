<?php
use PHPUnit\Framework\TestCase;

// Load the JSON response helper
require_once GYRO_ROOT_DIR . 'modules/api/lib/helpers/jsonresponse.cls.php';

/**
 * Test DAO with bool and float fields for JsonResponse tests
 */
class DAOJsonResponseTestTypes extends DataObjectBase {
	public $id;
	public $active;
	public $score;

	protected function create_table_object() {
		return new DBTable(
			'jsonresp_types_test',
			array(
				new DBFieldInt('id', null, DBFieldInt::AUTOINCREMENT | DBFieldInt::NOT_NULL),
				new DBFieldBool('active', false, DBField::NOT_NULL),
				new DBFieldFloat('score', null, DBField::NONE),
			),
			'id',
			null,
			null,
			new DBDriverMySqlMock()
		);
	}
}

/**
 * Test DAO with INTERNAL fields for JsonResponse tests
 */
class DAOJsonResponseTestInternal extends DataObjectBase {
	public $id;
	public $name;
	public $secret;

	protected function create_table_object() {
		return new DBTable(
			'jsonresp_internal_test',
			array(
				new DBFieldInt('id', null, DBFieldInt::AUTOINCREMENT | DBFieldInt::NOT_NULL),
				new DBFieldText('name', 100, null, DBField::NOT_NULL),
				new DBFieldText('secret', 100, null, DBField::INTERNAL),
			),
			'id',
			null,
			null,
			new DBDriverMySqlMock()
		);
	}
}

class JsonResponseTest extends TestCase {
	public function test_dao_to_array_basic() {
		$dao = new DAOStudentsTest();
		$dao->id = 42;
		$dao->name = 'Alice';
		$dao->modificationdate = '2026-01-01 12:00:00';

		$result = JsonResponse::dao_to_array($dao);

		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey('name', $result);
		$this->assertArrayHasKey('modificationdate', $result);
		$this->assertSame(42, $result['id']);
		$this->assertSame('Alice', $result['name']);
		$this->assertSame('2026-01-01 12:00:00', $result['modificationdate']);
	}

	public function test_dao_to_array_null_values() {
		$dao = new DAOStudentsTest();
		$dao->id = 1;
		$dao->name = null;
		$dao->modificationdate = null;

		$result = JsonResponse::dao_to_array($dao);

		$this->assertSame(1, $result['id']);
		$this->assertNull($result['name']);
		$this->assertNull($result['modificationdate']);
	}

	public function test_dao_to_array_excludes_internal_fields() {
		$dao = new DAOJsonResponseTestInternal();
		$dao->id = 1;
		$dao->name = 'visible';
		$dao->secret = 'hidden';

		$result = JsonResponse::dao_to_array($dao);

		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey('name', $result);
		$this->assertArrayNotHasKey('secret', $result);
	}

	public function test_dao_to_array_includes_internal_when_requested() {
		$dao = new DAOJsonResponseTestInternal();
		$dao->id = 1;
		$dao->secret = 'now-visible';

		$result = JsonResponse::dao_to_array($dao, true);

		$this->assertArrayHasKey('secret', $result);
		$this->assertSame('now-visible', $result['secret']);
	}

	public function test_dao_to_array_type_casting_int() {
		$dao = new DAOStudentsTest();
		$dao->id = '99';
		$dao->name = 'Bob';
		$dao->modificationdate = '2026-01-01 00:00:00';

		$result = JsonResponse::dao_to_array($dao);

		$this->assertSame(99, $result['id']);
		$this->assertIsInt($result['id']);
	}

	public function test_dao_to_array_type_casting_bool() {
		$dao = new DAOJsonResponseTestTypes();
		$dao->id = 1;
		$dao->active = 'TRUE';
		$dao->score = 0;

		$result = JsonResponse::dao_to_array($dao);
		$this->assertTrue($result['active']);

		$dao->active = 'FALSE';
		$result = JsonResponse::dao_to_array($dao);
		$this->assertFalse($result['active']);
	}

	public function test_dao_to_array_type_casting_float() {
		$dao = new DAOJsonResponseTestTypes();
		$dao->id = 1;
		$dao->active = 'TRUE';
		$dao->score = '3.14';

		$result = JsonResponse::dao_to_array($dao);
		$this->assertSame(3.14, $result['score']);
		$this->assertIsFloat($result['score']);
	}
}
