<?php
use PHPUnit\Framework\TestCase;

// Load API module classes
require_once GYRO_ROOT_DIR . 'modules/api/lib/helpers/jsonresponse.cls.php';
require_once GYRO_ROOT_DIR . 'modules/api/lib/helpers/openapigenerator.cls.php';

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

/**
 * Test DAO for OpenAPI tests — has various field types
 */
class DAOOpenApiTestModel extends DataObjectBase {
	public $id;
	public $name;
	public $email;
	public $score;
	public $active;
	public $birthday;
	public $created_at;
	public $status;
	public $secret;

	protected function create_table_object() {
		return new DBTable(
			'openapi_test',
			array(
				new DBFieldInt('id', null, DBFieldInt::AUTOINCREMENT | DBFieldInt::NOT_NULL),
				new DBFieldText('name', 100, null, DBField::NOT_NULL),
				new DBFieldText('email', 255, null, DBField::NONE),
				new DBFieldFloat('score', 0.0, DBField::NONE),
				new DBFieldBool('active', true, DBField::NOT_NULL),
				new DBFieldDate('birthday', null, DBField::NONE),
				new DBFieldDateTime('created_at', null, DBField::NONE),
				new DBFieldEnum('status', array('draft', 'published', 'archived'), 'draft', DBField::NOT_NULL),
				new DBFieldText('secret', 100, null, DBField::INTERNAL),
			),
			'id',
			null,
			null,
			new DBDriverMySqlMock()
		);
	}
}

class OpenApiGeneratorTest extends TestCase {
	protected function setUp(): void {
		// Reset RestApiController static state
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

	public function test_generate_returns_valid_structure() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate();

		$this->assertSame('3.0.3', $spec['openapi']);
		$this->assertArrayHasKey('info', $spec);
		$this->assertArrayHasKey('paths', $spec);
		$this->assertArrayHasKey('components', $spec);
		$this->assertSame('Gyro REST API', $spec['info']['title']);
	}

	public function test_generate_includes_server_url() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate('https://example.com');

		$this->assertArrayHasKey('servers', $spec);
		$this->assertSame('https://example.com', $spec['servers'][0]['url']);
	}

	public function test_generate_no_server_when_empty() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate();

		$this->assertArrayNotHasKey('servers', $spec);
	}

	public function test_generate_has_api_index_path() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate();

		$this->assertArrayHasKey('/api', $spec['paths']);
		$this->assertArrayHasKey('get', $spec['paths']['/api']);
	}

	public function test_generate_creates_model_paths() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate();

		$this->assertArrayHasKey('/api/openapi_test', $spec['paths']);
		$this->assertArrayHasKey('/api/openapi_test/{id}', $spec['paths']);
		$this->assertArrayHasKey('/api/openapi_test/schema', $spec['paths']);
	}

	public function test_list_path_has_get_and_post() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate();

		$list_path = $spec['paths']['/api/openapi_test'];
		$this->assertArrayHasKey('get', $list_path);
		$this->assertArrayHasKey('post', $list_path);
	}

	public function test_item_path_has_get_put_delete() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate();

		$item_path = $spec['paths']['/api/openapi_test/{id}'];
		$this->assertArrayHasKey('get', $item_path);
		$this->assertArrayHasKey('put', $item_path);
		$this->assertArrayHasKey('delete', $item_path);
	}

	public function test_component_schemas_generated() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate();

		$schemas = $spec['components']['schemas'];
		$this->assertArrayHasKey('OpenapiTest', $schemas);
		$this->assertArrayHasKey('OpenapiTestInput', $schemas);
	}

	public function test_schema_excludes_internal_fields() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate();

		$schema = $spec['components']['schemas']['OpenapiTest'];
		$this->assertArrayNotHasKey('secret', $schema['properties']);
		$this->assertArrayHasKey('name', $schema['properties']);
	}

	public function test_schema_input_excludes_autoincrement_pk() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate();

		$input_schema = $spec['components']['schemas']['OpenapiTestInput'];
		$this->assertArrayNotHasKey('id', $input_schema['properties']);
		$this->assertArrayHasKey('name', $input_schema['properties']);
	}

	public function test_field_type_mapping() {
		$dao = new DAOOpenApiTestModel();
		$schema = OpenApiGenerator::build_schema($dao, false);

		$this->assertSame('integer', $schema['properties']['id']['type']);
		$this->assertSame('string', $schema['properties']['name']['type']);
		$this->assertSame('string', $schema['properties']['email']['type']);
		$this->assertSame('number', $schema['properties']['score']['type']);
		$this->assertSame('boolean', $schema['properties']['active']['type']);
		$this->assertSame('string', $schema['properties']['birthday']['type']);
		$this->assertSame('date', $schema['properties']['birthday']['format']);
		$this->assertSame('string', $schema['properties']['created_at']['type']);
		$this->assertSame('date-time', $schema['properties']['created_at']['format']);
	}

	public function test_enum_field_has_values() {
		$dao = new DAOOpenApiTestModel();
		$schema = OpenApiGenerator::build_schema($dao, false);

		$this->assertArrayHasKey('enum', $schema['properties']['status']);
		$this->assertSame(array('draft', 'published', 'archived'), $schema['properties']['status']['enum']);
	}

	public function test_text_field_has_max_length() {
		$dao = new DAOOpenApiTestModel();
		$schema = OpenApiGenerator::build_schema($dao, false);

		$this->assertSame(100, $schema['properties']['name']['maxLength']);
	}

	public function test_nullable_field() {
		$dao = new DAOOpenApiTestModel();
		$schema = OpenApiGenerator::build_schema($dao, false);

		// email allows null
		$this->assertTrue($schema['properties']['email']['nullable'] ?? false);
		// name is NOT_NULL — should not have nullable
		$this->assertArrayNotHasKey('nullable', $schema['properties']['name']);
	}

	public function test_required_fields() {
		$dao = new DAOOpenApiTestModel();
		$schema = OpenApiGenerator::build_schema($dao, false);

		$this->assertContains('name', $schema['required']);
	}

	public function test_schema_name_conversion() {
		$this->assertSame('UsersTest', OpenApiGenerator::schema_name('users_test'));
		$this->assertSame('Users', OpenApiGenerator::schema_name('users'));
		$this->assertSame('MyLongTable', OpenApiGenerator::schema_name('my_long_table'));
		$this->assertSame('SomeModel', OpenApiGenerator::schema_name('some-model'));
	}

	public function test_field_to_schema_basic() {
		$int_field = new DBFieldInt('id');
		$result = OpenApiGenerator::field_to_schema($int_field);
		$this->assertSame('integer', $result['type']);
		$this->assertSame('int64', $result['format']);

		$bool_field = new DBFieldBool('active');
		$result = OpenApiGenerator::field_to_schema($bool_field);
		$this->assertSame('boolean', $result['type']);
		$this->assertArrayNotHasKey('format', $result);
	}

	public function test_list_parameters_present() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		$spec = OpenApiGenerator::generate();

		$get_op = $spec['paths']['/api/openapi_test']['get'];
		$this->assertNotEmpty($get_op['parameters']);

		$param_names = array_column($get_op['parameters'], 'name');
		$this->assertContains('page', $param_names);
		$this->assertContains('per_page', $param_names);
		$this->assertContains('sort', $param_names);
		$this->assertContains('order', $param_names);
	}

	public function test_multiple_models() {
		RestApiController::register_model('openapi_test', 'DAOOpenApiTestModel');
		RestApiController::register_model('studentstest', 'DAOStudentsTest');
		$spec = OpenApiGenerator::generate();

		$this->assertArrayHasKey('/api/openapi_test', $spec['paths']);
		$this->assertArrayHasKey('/api/studentstest', $spec['paths']);
		$this->assertArrayHasKey('OpenapiTest', $spec['components']['schemas']);
		$this->assertArrayHasKey('Studentstest', $spec['components']['schemas']);
	}

	public function test_get_routes_includes_openapi_route() {
		$controller = new RestApiController();
		$routes = $controller->get_routes();

		$this->assertCount(3, $routes);
		// First is exact match for /api/
		$this->assertInstanceOf(ExactMatchRoute::class, $routes[0]);
		// Second is exact match for /api/openapi.json
		$this->assertInstanceOf(ExactMatchRoute::class, $routes[1]);
		// Third is catch-all for /api/{...}
		$this->assertInstanceOf(RouteBase::class, $routes[2]);
	}
}
