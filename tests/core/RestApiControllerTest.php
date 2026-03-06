<?php
use PHPUnit\Framework\TestCase;

// Load API module classes
require_once GYRO_ROOT_DIR . 'modules/api/lib/helpers/jsonresponse.cls.php';

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
require_once GYRO_ROOT_DIR . 'modules/api/controller/restapi.controller.php';

class RestApiControllerTest extends TestCase {
	protected function setUp(): void {
		// Reset static state between tests
		$ref = new ReflectionClass(RestApiController::class);

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
		RestApiController::register_model('users', 'DAOUsers');
		$models = RestApiController::get_registered_models();

		$this->assertArrayHasKey('users', $models);
		$this->assertSame('DAOUsers', $models['users']);
	}

	public function test_register_multiple_models() {
		RestApiController::register_model('users', 'DAOUsers');
		RestApiController::register_model('studentstest', 'DAOStudentsTest');
		RestApiController::register_model('roomstest', 'DAORoomsTest');

		$models = RestApiController::get_registered_models();

		// At least these 3 should be present (discovery may add more)
		$this->assertGreaterThanOrEqual(3, count($models));
		$this->assertArrayHasKey('users', $models);
		$this->assertArrayHasKey('studentstest', $models);
		$this->assertArrayHasKey('roomstest', $models);
	}

	public function test_exclude_table() {
		RestApiController::register_model('sessions', 'DAOSessions');
		RestApiController::register_model('studentstest', 'DAOStudentsTest');
		RestApiController::exclude_table('studentstest');

		$models = RestApiController::get_registered_models();

		$this->assertArrayHasKey('sessions', $models);
		// Excluded tables should not appear even if registered before exclude
		// (exclude prevents auto-discovery, but register is explicit)
		// So studentstest IS present because it was explicitly registered
		$this->assertArrayHasKey('studentstest', $models);
	}

	public function test_exclude_prevents_auto_discovery() {
		// Exclude before discovery runs — excluded tables won't be added by discovery
		RestApiController::exclude_table('sometable');
		$models = RestApiController::get_registered_models();

		$this->assertArrayNotHasKey('sometable', $models);
	}

	public function test_get_routes_returns_routes() {
		$controller = new RestApiController();
		$routes = $controller->get_routes();

		$this->assertCount(3, $routes);
		$this->assertInstanceOf(ExactMatchRoute::class, $routes[0]);
		$this->assertInstanceOf(ExactMatchRoute::class, $routes[1]);
		$this->assertInstanceOf(RouteBase::class, $routes[2]);
	}

	public function test_explicit_registration_preserved_after_discovery() {
		// Register with a custom class before discovery runs
		RestApiController::register_model('studentstest', 'CustomDAOStudents');
		$models = RestApiController::get_registered_models();

		// Explicit registration should not be overwritten by discovery
		$this->assertSame('CustomDAOStudents', $models['studentstest']);
	}

	public function test_get_json_type_mapping() {
		$controller = new RestApiController();
		$method = new ReflectionMethod($controller, 'get_json_type');
		$method->setAccessible(true);

		$this->assertSame('integer', $method->invoke($controller, new DBFieldInt('id')));
		$this->assertSame('string', $method->invoke($controller, new DBFieldText('name', 100)));
		$this->assertSame('number', $method->invoke($controller, new DBFieldFloat('score')));
		$this->assertSame('boolean', $method->invoke($controller, new DBFieldBool('active')));
		$this->assertSame('date', $method->invoke($controller, new DBFieldDate('birthday')));
		$this->assertSame('datetime', $method->invoke($controller, new DBFieldDateTime('created_at')));
		$this->assertSame('time', $method->invoke($controller, new DBFieldTime('start_time')));
		$this->assertSame('enum', $method->invoke($controller, new DBFieldEnum('status', array('A', 'B'))));
		$this->assertSame('object', $method->invoke($controller, new DBFieldSerialized('meta')));
	}

	public function test_status_to_details() {
		$controller = new RestApiController();
		$method = new ReflectionMethod($controller, 'status_to_details');
		$method->setAccessible(true);

		$status = new Status('Something went wrong');
		$details = $method->invoke($controller, $status);

		$this->assertIsArray($details);
		$this->assertNotEmpty($details);
		$this->assertStringContainsString('Something went wrong', $details[0]);
	}

	public function test_get_request_method_default() {
		$controller = new RestApiController();
		$method = new ReflectionMethod($controller, 'get_request_method');
		$method->setAccessible(true);

		// Default should be GET when REQUEST_METHOD is not set
		$old = $_SERVER['REQUEST_METHOD'] ?? null;
		unset($_SERVER['REQUEST_METHOD']);
		$this->assertSame('GET', $method->invoke($controller));
		if ($old !== null) {
			$_SERVER['REQUEST_METHOD'] = $old;
		}
	}

	public function test_get_request_method_override() {
		$controller = new RestApiController();
		$method = new ReflectionMethod($controller, 'get_request_method');
		$method->setAccessible(true);

		$old_method = $_SERVER['REQUEST_METHOD'] ?? null;
		$old_override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PUT';

		$this->assertSame('PUT', $method->invoke($controller));

		// Cleanup
		if ($old_method !== null) {
			$_SERVER['REQUEST_METHOD'] = $old_method;
		} else {
			unset($_SERVER['REQUEST_METHOD']);
		}
		if ($old_override !== null) {
			$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = $old_override;
		} else {
			unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
		}
	}

	public function test_read_json_body_returns_false_for_empty() {
		$controller = new RestApiController();
		$method = new ReflectionMethod($controller, 'read_json_body');
		$method->setAccessible(true);

		// php://input is empty in CLI context
		$result = $method->invoke($controller);
		$this->assertFalse($result);
	}

	public function test_dao_to_array_via_json_response() {
		$dao = new DAOStudentsTest();
		$dao->id = 1;
		$dao->name = 'Test Student';
		$dao->modificationdate = '2026-03-05 10:00:00';

		$result = JsonResponse::dao_to_array($dao);

		$this->assertSame(1, $result['id']);
		$this->assertSame('Test Student', $result['name']);
		$this->assertSame('2026-03-05 10:00:00', $result['modificationdate']);
	}

	public function test_schema_type_covers_all_field_types() {
		$controller = new RestApiController();
		$method = new ReflectionMethod($controller, 'get_json_type');
		$method->setAccessible(true);

		// Every field type should return a non-empty string
		$fields = array(
			new DBFieldInt('a'),
			new DBFieldText('b', 100),
			new DBFieldFloat('c'),
			new DBFieldBool('d'),
			new DBFieldDate('e'),
			new DBFieldDateTime('f'),
			new DBFieldTime('g'),
			new DBFieldEnum('h', array('X')),
			new DBFieldBlob('i'),
			new DBFieldSerialized('j'),
		);

		foreach ($fields as $field) {
			$type = $method->invoke($controller, $field);
			$this->assertNotEmpty($type, 'Type should not be empty for ' . get_class($field));
			$this->assertIsString($type);
		}
	}
}
