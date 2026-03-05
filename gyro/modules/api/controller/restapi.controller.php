<?php
/**
 * Auto-REST-API Controller.
 *
 * Automatically generates REST endpoints for all DAO models:
 *
 *   GET    /api/{table}          — List records (with paging, filtering, sorting)
 *   GET    /api/{table}/{id}     — Show single record
 *   POST   /api/{table}          — Create record
 *   PUT    /api/{table}/{id}     — Update record
 *   DELETE /api/{table}/{id}     — Delete record
 *   GET    /api/{table}/schema   — Show table schema (fields, types, relations)
 *   GET    /api                  — List all available API endpoints
 *
 * The models describe themselves — no configuration needed.
 *
 * @since 0.9
 * @ingroup API
 */
class RestApiController extends ControllerBase {
	/**
	 * Maximum items per page (hard limit)
	 */
	const MAX_PER_PAGE = 200;

	/**
	 * Default items per page
	 */
	const DEFAULT_PER_PAGE = 25;

	/**
	 * Registry of exposed table names => DAO class names
	 *
	 * @var array
	 */
	private static $models = array();

	/**
	 * Tables excluded from the API
	 *
	 * @var array
	 */
	private static $excluded_tables = array();

	/**
	 * Whether discovery has run
	 *
	 * @var bool
	 */
	private static $discovered = false;

	/**
	 * Registers routes. Called by the framework.
	 *
	 * @return array
	 */
	public function get_routes() {
		return array(
			new ExactMatchRoute('api/', $this, 'api_index', null),
			new ExactMatchRoute('api/openapi.json', $this, 'api_openapi', null),
			new RouteBase('api/', $this, 'api_dispatch', null),
		);
	}

	// -------------------------------------------------------
	// Static configuration API
	// -------------------------------------------------------

	/**
	 * Explicitly register a model for the REST API.
	 *
	 * @param string $table_name The table/endpoint name
	 * @param string $dao_class The DAO class name (e.g. 'DAOUsers')
	 */
	public static function register_model(string $table_name, string $dao_class): void {
		self::$models[$table_name] = $dao_class;
	}

	/**
	 * Exclude a table from the auto-discovery.
	 *
	 * @param string $table_name
	 */
	public static function exclude_table(string $table_name): void {
		self::$excluded_tables[$table_name] = true;
	}

	/**
	 * Returns all registered models (runs discovery if needed).
	 *
	 * @return array table_name => dao_class_name
	 */
	public static function get_registered_models(): array {
		self::ensure_discovery();
		return self::$models;
	}

	// -------------------------------------------------------
	// Auto-Discovery
	// -------------------------------------------------------

	/**
	 * Discover models if not already done.
	 */
	private static function ensure_discovery(): void {
		if (self::$discovered) {
			return;
		}
		self::$discovered = true;

		// Use the CLI model discovery if available
		if (class_exists('ModelListCommand', false)) {
			$infos = ModelListCommand::discover_models();
			foreach ($infos as $info) {
				$table = $info['table'];
				if (!isset(self::$excluded_tables[$table]) && !isset(self::$models[$table])) {
					self::$models[$table] = $info['class'];
				}
			}
		}
	}

	// -------------------------------------------------------
	// Actions
	// -------------------------------------------------------

	/**
	 * GET /api — List all available endpoints
	 */
	public function action_api_index($page_data) {
		$this->require_method('GET');

		$models = self::get_registered_models();
		$endpoints = array();
		foreach ($models as $table => $class) {
			$endpoints[] = array(
				'table' => $table,
				'endpoints' => array(
					'list' => 'GET /api/' . $table,
					'show' => 'GET /api/' . $table . '/{id}',
					'create' => 'POST /api/' . $table,
					'update' => 'PUT /api/' . $table . '/{id}',
					'delete' => 'DELETE /api/' . $table . '/{id}',
					'schema' => 'GET /api/' . $table . '/schema',
				),
			);
		}

		JsonResponse::success(array(
			'api' => 'Gyro REST API',
			'version' => '1.0',
			'models' => $endpoints,
		));
	}

	/**
	 * GET /api/openapi.json — OpenAPI 3.0 specification
	 */
	public function action_api_openapi($page_data) {
		$this->require_method('GET');

		$base_url = '';
		if (class_exists('Url', false)) {
			$base_url = rtrim(Url::current()->clear_query()->build(), '/');
			// Remove /api/openapi.json from the URL
			$base_url = preg_replace('#/api/openapi\.json$#', '', $base_url);
		}

		$spec = OpenApiGenerator::generate($base_url);
		JsonResponse::send($spec);
	}

	/**
	 * Dispatch /api/{table}[/{id}] based on HTTP method and path
	 */
	public function action_api_dispatch($page_data) {
		$pathstack = $page_data->get_pathstack();
		$table_name = $pathstack->shift();

		if (empty($table_name)) {
			JsonResponse::error('No table specified.', 400);
		}

		$models = self::get_registered_models();
		if (!isset($models[$table_name])) {
			JsonResponse::error("Unknown model '$table_name'.", 404);
		}

		$dao_class = $models[$table_name];
		$id_or_action = $pathstack->shift();
		$method = $this->get_request_method();

		// GET /api/{table}/schema
		if ($id_or_action === 'schema' && $method === 'GET') {
			$this->handle_schema($dao_class);
			return;
		}

		// Dispatch by method
		if ($method === 'GET' && empty($id_or_action)) {
			$this->handle_list($dao_class, $page_data);
		} elseif ($method === 'GET' && !empty($id_or_action)) {
			$this->handle_show($dao_class, $id_or_action);
		} elseif ($method === 'POST' && empty($id_or_action)) {
			$this->handle_create($dao_class);
		} elseif ($method === 'PUT' && !empty($id_or_action)) {
			$this->handle_update($dao_class, $id_or_action);
		} elseif ($method === 'DELETE' && !empty($id_or_action)) {
			$this->handle_delete($dao_class, $id_or_action);
		} else {
			JsonResponse::error('Method not allowed.', 405);
		}
	}

	// -------------------------------------------------------
	// CRUD Handlers
	// -------------------------------------------------------

	/**
	 * GET /api/{table} — List with paging, filtering, sorting
	 */
	protected function handle_list(string $dao_class, $page_data): void {
		$dao = new $dao_class();
		/** @var DataObjectBase $dao */

		// Paging
		$page = max(1, (int)Arr::get_item($_GET, 'page', 1));
		$per_page = min(self::MAX_PER_PAGE, max(1, (int)Arr::get_item($_GET, 'per_page', self::DEFAULT_PER_PAGE)));
		$offset = ($page - 1) * $per_page;

		// Filtering: ?filter[field]=value
		$filters = Arr::get_item($_GET, 'filter', array());
		if (is_array($filters)) {
			$fields = $dao->get_table_fields();
			foreach ($filters as $field_name => $value) {
				if (isset($fields[$field_name]) && !$fields[$field_name]->has_policy(DBField::INTERNAL)) {
					$dao->add_where($field_name, '=', $value);
				}
			}
		}

		// Sorting: ?sort=field&order=asc|desc
		$sort_field = Arr::get_item($_GET, 'sort', '');
		$sort_order = strtolower(Arr::get_item($_GET, 'order', 'asc'));
		if ($sort_field !== '') {
			$fields = $dao->get_table_fields();
			if (isset($fields[$sort_field]) && !$fields[$sort_field]->has_policy(DBField::INTERNAL)) {
				$dao->sort($sort_field, $sort_order === 'desc' ? DataObjectBase::DESC : DataObjectBase::ASC);
			}
		}

		// Count total
		$count_dao = clone $dao;
		$total = $count_dao->count();

		// Apply limit and fetch
		$dao->limit($offset, $per_page);
		$items = $dao->find_array();

		$data = array();
		foreach ($items as $item) {
			$data[] = JsonResponse::dao_to_array($item);
		}

		JsonResponse::success(array(
			'data' => $data,
			'meta' => array(
				'total' => (int)$total,
				'page' => $page,
				'per_page' => $per_page,
				'total_pages' => $total > 0 ? (int)ceil($total / $per_page) : 0,
			),
		));
	}

	/**
	 * GET /api/{table}/{id} — Show single record
	 */
	protected function handle_show(string $dao_class, string $id): void {
		$dao = $this->find_by_id($dao_class, $id);
		if ($dao === false) {
			JsonResponse::error('Record not found.', 404);
		}
		JsonResponse::success(array('data' => JsonResponse::dao_to_array($dao)));
	}

	/**
	 * POST /api/{table} — Create a new record
	 */
	protected function handle_create(string $dao_class): void {
		$input = $this->read_json_body();
		if ($input === false) {
			JsonResponse::error('Invalid JSON body.', 400);
		}

		$dao = new $dao_class();
		/** @var DataObjectBase $dao */
		$dao->set_default_values();

		// Set values from input, respecting INTERNAL flag
		$cleaned = $dao->unset_internals($input);
		$dao->read_from_array($cleaned);

		// Validate
		$status = $dao->validate();
		if (!$status->is_ok()) {
			JsonResponse::error('Validation failed.', 422, $this->status_to_details($status));
		}

		// Insert
		$status = $dao->insert();
		if (!$status->is_ok()) {
			JsonResponse::error('Insert failed.', 500, $this->status_to_details($status));
		}

		JsonResponse::success(
			array('data' => JsonResponse::dao_to_array($dao)),
			201
		);
	}

	/**
	 * PUT /api/{table}/{id} — Update existing record
	 */
	protected function handle_update(string $dao_class, string $id): void {
		$dao = $this->find_by_id($dao_class, $id);
		if ($dao === false) {
			JsonResponse::error('Record not found.', 404);
		}

		$input = $this->read_json_body();
		if ($input === false) {
			JsonResponse::error('Invalid JSON body.', 400);
		}

		// Apply only non-internal, non-key fields
		$cleaned = $dao->unset_internals($input);
		$dao->read_from_array($cleaned);

		// Validate
		$status = $dao->validate();
		if (!$status->is_ok()) {
			JsonResponse::error('Validation failed.', 422, $this->status_to_details($status));
		}

		// Update
		$status = $dao->update();
		if (!$status->is_ok()) {
			JsonResponse::error('Update failed.', 500, $this->status_to_details($status));
		}

		JsonResponse::success(array('data' => JsonResponse::dao_to_array($dao)));
	}

	/**
	 * DELETE /api/{table}/{id} — Delete record
	 */
	protected function handle_delete(string $dao_class, string $id): void {
		$dao = $this->find_by_id($dao_class, $id);
		if ($dao === false) {
			JsonResponse::error('Record not found.', 404);
		}

		$status = $dao->delete();
		if (!$status->is_ok()) {
			JsonResponse::error('Delete failed.', 500, $this->status_to_details($status));
		}

		JsonResponse::success(array('message' => 'Deleted.'), 200);
	}

	/**
	 * GET /api/{table}/schema — Show model schema
	 */
	protected function handle_schema(string $dao_class): void {
		$dao = new $dao_class();
		/** @var DataObjectBase $dao */

		$fields_info = array();
		foreach ($dao->get_table_fields() as $name => $field) {
			if ($field->has_policy(DBField::INTERNAL)) {
				continue;
			}
			$info = array(
				'name' => $name,
				'type' => $this->get_json_type($field),
				'nullable' => $field->get_null_allowed(),
			);
			$default = $field->get_field_default();
			if ($default !== null) {
				$info['default'] = $default;
			}
			// Enum values (via reflection, since 'allowed' is protected)
			if ($field instanceof DBFieldEnum) {
				try {
					$ref = new ReflectionProperty($field, 'allowed');
					$ref->setAccessible(true);
					$info['values'] = $ref->getValue($field);
				} catch (ReflectionException $e) {
					// Skip if not available
				}
			}

			$fields_info[] = $info;
		}

		$keys_info = array();
		foreach ($dao->get_table_keys() as $name => $field) {
			$keys_info[] = $name;
		}

		$relations_info = array();
		foreach ($dao->get_table_relations() as $relation) {
			$type_label = match ($relation->get_type()) {
				DBRelation::ONE_TO_ONE => 'one_to_one',
				DBRelation::ONE_TO_MANY => 'one_to_many',
				DBRelation::MANY_TO_MANY => 'many_to_many',
				default => 'unknown',
			};
			$field_pairs = array();
			foreach ($relation->get_fields() as $field_rel) {
				$field_pairs[] = array(
					'source' => $field_rel->get_source_field_name(),
					'target' => $field_rel->get_target_field_name(),
				);
			}
			$relations_info[] = array(
				'target_table' => $relation->get_target_table_name(),
				'type' => $type_label,
				'fields' => $field_pairs,
			);
		}

		JsonResponse::success(array(
			'table' => $dao->get_table_name(),
			'primary_key' => $keys_info,
			'fields' => $fields_info,
			'relations' => $relations_info,
		));
	}

	// -------------------------------------------------------
	// Helper methods
	// -------------------------------------------------------

	/**
	 * Find a record by its primary key value(s)
	 *
	 * @param string $dao_class
	 * @param string $id Pipe-separated for composite keys: "val1|val2"
	 * @return DataObjectBase|false
	 */
	protected function find_by_id(string $dao_class, string $id) {
		$dao = new $dao_class();
		/** @var DataObjectBase $dao */

		$keys = $dao->get_table_keys();
		$key_names = array_keys($keys);

		if (count($key_names) === 1) {
			// Simple primary key
			$found = $dao->get($key_names[0], $id);
		} else {
			// Composite primary key: id is pipe-separated
			$id_parts = explode('|', $id);
			if (count($id_parts) !== count($key_names)) {
				return false;
			}
			foreach ($key_names as $i => $key_name) {
				$dao->add_where($key_name, '=', $id_parts[$i]);
			}
			$dao->limit(0, 1);
			$count = $dao->find();
			$found = ($count > 0) ? $dao->fetch() : false;
		}

		return $found ? $dao : false;
	}

	/**
	 * Read and decode JSON from the request body
	 *
	 * @return array|false
	 */
	protected function read_json_body() {
		$raw = file_get_contents('php://input');
		if (empty($raw)) {
			return false;
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			return false;
		}
		return $decoded;
	}

	/**
	 * Get the HTTP request method, supporting X-HTTP-Method-Override
	 *
	 * @return string
	 */
	protected function get_request_method(): string {
		$method = strtoupper(Arr::get_item($_SERVER, 'REQUEST_METHOD', 'GET'));
		// Support method override for clients that can't send PUT/DELETE
		$override = Arr::get_item($_SERVER, 'HTTP_X_HTTP_METHOD_OVERRIDE', '');
		if ($method === 'POST' && in_array(strtoupper($override), array('PUT', 'DELETE', 'PATCH'))) {
			$method = strtoupper($override);
		}
		return $method;
	}

	/**
	 * Assert that the request uses the expected HTTP method
	 */
	protected function require_method(string $expected): void {
		$actual = $this->get_request_method();
		if ($actual !== $expected) {
			JsonResponse::error('Method not allowed. Expected ' . $expected . '.', 405);
		}
	}

	/**
	 * Convert a Status object to an array of error details
	 *
	 * @param Status $status
	 * @return array
	 */
	protected function status_to_details(Status $status): array {
		$details = array();
		if (method_exists($status, 'get_messages')) {
			foreach ($status->get_messages() as $msg) {
				$details[] = is_string($msg) ? $msg : (string)$msg;
			}
		} elseif (method_exists($status, 'to_string')) {
			$details[] = $status->to_string();
		}
		return $details;
	}

	/**
	 * Map a DBField to a JSON-friendly type name
	 */
	protected function get_json_type(IDBField $field): string {
		$class = get_class($field);
		return match ($class) {
			'DBFieldInt' => 'integer',
			'DBFieldFloat' => 'number',
			'DBFieldBool' => 'boolean',
			'DBFieldDate' => 'date',
			'DBFieldDateTime' => 'datetime',
			'DBFieldTime' => 'time',
			'DBFieldEnum' => 'enum',
			'DBFieldSet' => 'set',
			'DBFieldBlob' => 'binary',
			'DBFieldSerialized' => 'object',
			'DBFieldText', 'DBFieldTextEmail' => 'string',
			default => 'string',
		};
	}
}
