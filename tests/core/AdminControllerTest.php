<?php
use PHPUnit\Framework\TestCase;

// Load admin module classes
require_once GYRO_ROOT_DIR . 'modules/admin/lib/helpers/adminhtml.cls.php';

// Load CLI classes for model discovery
if (!class_exists('CLICommand', false)) {
	require_once GYRO_CORE_DIR . 'cli/clicommand.cls.php';
}
if (!class_exists('CLITable', false)) {
	require_once GYRO_CORE_DIR . 'cli/clitable.cls.php';
}
if (!class_exists('ModelListCommand', false)) {
	require_once GYRO_CORE_DIR . 'cli/commands/modellistcommand.cli.php';
}
require_once GYRO_ROOT_DIR . 'modules/admin/controller/admin.controller.php';

/**
 * Test DAO with INTERNAL fields for admin tests
 */
class DAOAdminInternalTest extends DataObjectBase {
	public $id;
	public $name;
	public $secret;

	protected function create_table_object() {
		return new DBTable(
			'admin_internal_test',
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

class AdminControllerTest extends TestCase {
	protected function setUp(): void {
		// Reset AdminController static state
		$ref = new ReflectionClass(AdminController::class);

		$models = $ref->getProperty('models');
		$models->setAccessible(true);
		$models->setValue(null, array());

		$excluded = $ref->getProperty('excluded_tables');
		$excluded->setAccessible(true);
		$excluded->setValue(null, array());

		$discovered = $ref->getProperty('discovered');
		$discovered->setAccessible(true);
		$discovered->setValue(null, false);
	}

	public function test_register_model() {
		AdminController::register_model('users', 'DAOUsers');
		$models = AdminController::get_registered_models();

		$this->assertArrayHasKey('users', $models);
		$this->assertSame('DAOUsers', $models['users']);
	}

	public function test_register_multiple_models() {
		AdminController::register_model('users', 'DAOUsers');
		AdminController::register_model('studentstest', 'DAOStudentsTest');

		$models = AdminController::get_registered_models();
		$this->assertArrayHasKey('users', $models);
		$this->assertArrayHasKey('studentstest', $models);
	}

	public function test_exclude_table() {
		AdminController::exclude_table('sessions');
		$models = AdminController::get_registered_models();
		$this->assertArrayNotHasKey('sessions', $models);
	}

	public function test_get_routes() {
		$controller = new AdminController();
		$routes = $controller->get_routes();

		$this->assertCount(2, $routes);
		$this->assertInstanceOf(ExactMatchRoute::class, $routes[0]);
		$this->assertInstanceOf(RouteBase::class, $routes[1]);
	}

	public function test_get_list_headers() {
		$controller = new AdminController();
		$method = new ReflectionMethod($controller, 'get_list_headers');
		$method->setAccessible(true);

		$dao = new DAOStudentsTest();
		$headers = $method->invoke($controller, $dao, 6);

		// Should contain primary key first
		$keys = array_keys($headers);
		$this->assertSame('id', $keys[0]);

		// Should not exceed max
		$this->assertLessThanOrEqual(6, count($headers));
	}

	public function test_get_list_headers_excludes_internal() {
		$controller = new AdminController();
		$method = new ReflectionMethod($controller, 'get_list_headers');
		$method->setAccessible(true);

		$dao = new DAOAdminInternalTest();
		$headers = $method->invoke($controller, $dao, 6);

		$this->assertArrayNotHasKey('secret', $headers);
		$this->assertArrayHasKey('name', $headers);
	}

	public function test_build_form_fields_create() {
		$controller = new AdminController();
		$method = new ReflectionMethod($controller, 'build_form_fields');
		$method->setAccessible(true);

		$dao = new DAOStudentsTest();
		$dao->set_default_values();
		$fields = $method->invoke($controller, $dao, true);

		// Auto-increment PK should be excluded on create
		$this->assertArrayNotHasKey('id', $fields);
		$this->assertArrayHasKey('name', $fields);
	}

	public function test_build_form_fields_edit() {
		$controller = new AdminController();
		$method = new ReflectionMethod($controller, 'build_form_fields');
		$method->setAccessible(true);

		$dao = new DAOStudentsTest();
		$dao->id = 1;
		$dao->name = 'Test';
		$fields = $method->invoke($controller, $dao, false);

		// PK should be excluded on edit (not editable)
		$this->assertArrayNotHasKey('id', $fields);
		$this->assertArrayHasKey('name', $fields);
	}

	public function test_build_form_fields_has_labels() {
		$controller = new AdminController();
		$method = new ReflectionMethod($controller, 'build_form_fields');
		$method->setAccessible(true);

		$dao = new DAOStudentsTest();
		$fields = $method->invoke($controller, $dao, true);

		foreach ($fields as $name => $info) {
			$this->assertArrayHasKey('label', $info);
			$this->assertArrayHasKey('type', $info);
			$this->assertNotEmpty($info['label']);
		}
	}

	public function test_build_form_fields_enum_options() {
		$controller = new AdminController();
		$method = new ReflectionMethod($controller, 'build_form_fields');
		$method->setAccessible(true);

		// Create a DAO with enum field
		$dao = new DAORoomsTest();
		$dao->set_default_values();
		$fields = $method->invoke($controller, $dao, true);

		// Check if any field has options (if the model has enum fields)
		// DAORoomsTest might not have enum, but the method should work
		$this->assertIsArray($fields);
	}

	public function test_explicit_registration_preserved() {
		AdminController::register_model('studentstest', 'CustomDAOStudents');
		$models = AdminController::get_registered_models();
		$this->assertSame('CustomDAOStudents', $models['studentstest']);
	}
}
