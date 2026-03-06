<?php
/**
 * Generates an OpenAPI 3.0 specification from registered REST API models.
 *
 * Reads all DAO models registered with RestApiController and produces
 * a valid OpenAPI 3.0 JSON document describing every endpoint, request body,
 * response schema and query parameters.
 *
 * @since 0.10
 * @ingroup API
 */
class OpenApiGenerator {
	/**
	 * OpenAPI type mapping from DBField class names
	 */
	const TYPE_MAP = array(
		'DBFieldInt' => array('type' => 'integer', 'format' => 'int64'),
		'DBFieldFloat' => array('type' => 'number', 'format' => 'double'),
		'DBFieldBool' => array('type' => 'boolean', 'format' => null),
		'DBFieldDate' => array('type' => 'string', 'format' => 'date'),
		'DBFieldDateTime' => array('type' => 'string', 'format' => 'date-time'),
		'DBFieldTime' => array('type' => 'string', 'format' => 'time'),
		'DBFieldBlob' => array('type' => 'string', 'format' => 'binary'),
		'DBFieldSerialized' => array('type' => 'object', 'format' => null),
		'DBFieldText' => array('type' => 'string', 'format' => null),
		'DBFieldTextEmail' => array('type' => 'string', 'format' => 'email'),
		'DBFieldEnum' => array('type' => 'string', 'format' => null),
		'DBFieldSet' => array('type' => 'string', 'format' => null),
	);

	/**
	 * Generate the full OpenAPI 3.0 specification.
	 *
	 * @param string $base_url Optional base URL (e.g. "https://example.com")
	 * @return array The OpenAPI spec as an associative array
	 */
	public static function generate(string $base_url = ''): array {
		$models = RestApiController::get_registered_models();

		$spec = array(
			'openapi' => '3.0.3',
			'info' => array(
				'title' => 'Gyro REST API',
				'description' => 'Auto-generated API documentation for all registered DAO models.',
				'version' => '1.0.0',
			),
			'paths' => array(),
			'components' => array(
				'schemas' => array(),
			),
		);

		if ($base_url !== '') {
			$spec['servers'] = array(
				array('url' => $base_url),
			);
		}

		// GET /api — index
		$spec['paths']['/api'] = array(
			'get' => array(
				'summary' => 'List all available API endpoints',
				'operationId' => 'apiIndex',
				'tags' => array('Index'),
				'responses' => array(
					'200' => array(
						'description' => 'List of available models and their endpoints',
						'content' => array(
							'application/json' => array(
								'schema' => array(
									'type' => 'object',
									'properties' => array(
										'api' => array('type' => 'string'),
										'version' => array('type' => 'string'),
										'models' => array('type' => 'array', 'items' => array('type' => 'object')),
									),
								),
							),
						),
					),
				),
			),
		);

		foreach ($models as $table => $dao_class) {
			try {
				$dao = new $dao_class();
			} catch (\Throwable $e) {
				continue;
			}

			if (!($dao instanceof DataObjectBase)) {
				continue;
			}

			$schema_name = self::schema_name($table);
			$schema_input_name = $schema_name . 'Input';

			// Build component schemas
			$spec['components']['schemas'][$schema_name] = self::build_schema($dao, false);
			$spec['components']['schemas'][$schema_input_name] = self::build_schema($dao, true);

			$tag = ucfirst($table);
			$key_names = array_keys($dao->get_table_keys());
			$id_param = count($key_names) > 1
				? implode('|', $key_names) . ' (pipe-separated)'
				: ($key_names[0] ?? 'id');

			// Paths for this model
			$list_path = '/api/' . $table;
			$item_path = '/api/' . $table . '/{id}';
			$schema_path = '/api/' . $table . '/schema';

			// --- GET /api/{table} (list) ---
			$spec['paths'][$list_path] = array(
				'get' => array(
					'summary' => "List $table records",
					'operationId' => 'list' . $schema_name,
					'tags' => array($tag),
					'parameters' => self::list_parameters(),
					'responses' => array(
						'200' => array(
							'description' => "Paginated list of $table records",
							'content' => array(
								'application/json' => array(
									'schema' => array(
										'type' => 'object',
										'properties' => array(
											'data' => array(
												'type' => 'array',
												'items' => array('$ref' => '#/components/schemas/' . $schema_name),
											),
											'meta' => array(
												'type' => 'object',
												'properties' => array(
													'total' => array('type' => 'integer'),
													'page' => array('type' => 'integer'),
													'per_page' => array('type' => 'integer'),
													'total_pages' => array('type' => 'integer'),
												),
											),
										),
									),
								),
							),
						),
					),
				),
				'post' => array(
					'summary' => "Create a new $table record",
					'operationId' => 'create' . $schema_name,
					'tags' => array($tag),
					'requestBody' => array(
						'required' => true,
						'content' => array(
							'application/json' => array(
								'schema' => array('$ref' => '#/components/schemas/' . $schema_input_name),
							),
						),
					),
					'responses' => array(
						'201' => array(
							'description' => 'Record created',
							'content' => array(
								'application/json' => array(
									'schema' => array(
										'type' => 'object',
										'properties' => array(
											'data' => array('$ref' => '#/components/schemas/' . $schema_name),
										),
									),
								),
							),
						),
						'400' => self::error_response('Invalid JSON body'),
						'422' => self::error_response('Validation failed'),
					),
				),
			);

			// --- GET/PUT/DELETE /api/{table}/{id} ---
			$spec['paths'][$item_path] = array(
				'get' => array(
					'summary' => "Get a single $table record",
					'operationId' => 'show' . $schema_name,
					'tags' => array($tag),
					'parameters' => array(self::id_parameter($id_param)),
					'responses' => array(
						'200' => array(
							'description' => "A single $table record",
							'content' => array(
								'application/json' => array(
									'schema' => array(
										'type' => 'object',
										'properties' => array(
											'data' => array('$ref' => '#/components/schemas/' . $schema_name),
										),
									),
								),
							),
						),
						'404' => self::error_response('Record not found'),
					),
				),
				'put' => array(
					'summary' => "Update a $table record",
					'operationId' => 'update' . $schema_name,
					'tags' => array($tag),
					'parameters' => array(self::id_parameter($id_param)),
					'requestBody' => array(
						'required' => true,
						'content' => array(
							'application/json' => array(
								'schema' => array('$ref' => '#/components/schemas/' . $schema_input_name),
							),
						),
					),
					'responses' => array(
						'200' => array(
							'description' => 'Record updated',
							'content' => array(
								'application/json' => array(
									'schema' => array(
										'type' => 'object',
										'properties' => array(
											'data' => array('$ref' => '#/components/schemas/' . $schema_name),
										),
									),
								),
							),
						),
						'400' => self::error_response('Invalid JSON body'),
						'404' => self::error_response('Record not found'),
						'422' => self::error_response('Validation failed'),
					),
				),
				'delete' => array(
					'summary' => "Delete a $table record",
					'operationId' => 'delete' . $schema_name,
					'tags' => array($tag),
					'parameters' => array(self::id_parameter($id_param)),
					'responses' => array(
						'200' => array(
							'description' => 'Record deleted',
							'content' => array(
								'application/json' => array(
									'schema' => array(
										'type' => 'object',
										'properties' => array(
											'message' => array('type' => 'string'),
										),
									),
								),
							),
						),
						'404' => self::error_response('Record not found'),
					),
				),
			);

			// --- GET /api/{table}/schema ---
			$spec['paths'][$schema_path] = array(
				'get' => array(
					'summary' => "Get $table model schema",
					'operationId' => 'schema' . $schema_name,
					'tags' => array($tag),
					'responses' => array(
						'200' => array(
							'description' => "Schema definition for $table",
							'content' => array(
								'application/json' => array(
									'schema' => array(
										'type' => 'object',
										'properties' => array(
											'table' => array('type' => 'string'),
											'primary_key' => array('type' => 'array', 'items' => array('type' => 'string')),
											'fields' => array('type' => 'array', 'items' => array('type' => 'object')),
											'relations' => array('type' => 'array', 'items' => array('type' => 'object')),
										),
									),
								),
							),
						),
					),
				),
			);
		}

		return $spec;
	}

	/**
	 * Build a JSON Schema object for a DAO model.
	 *
	 * @param DataObjectBase $dao
	 * @param bool $input_only If true, exclude auto-generated fields (PK with AUTOINCREMENT)
	 * @return array
	 */
	public static function build_schema(DataObjectBase $dao, bool $input_only = false): array {
		$properties = array();
		$required = array();
		$key_names = array_keys($dao->get_table_keys());

		foreach ($dao->get_table_fields() as $name => $field) {
			if ($field->has_policy(DBField::INTERNAL)) {
				continue;
			}

			// For input schema, skip auto-increment primary keys
			if ($input_only && in_array($name, $key_names)) {
				$flags = $field->get_policy();
				if ($flags & DBFieldInt::AUTOINCREMENT) {
					continue;
				}
			}

			$prop = self::field_to_schema($field);

			// Track required fields (NOT_NULL without default)
			if (!$field->get_null_allowed() && $field->get_field_default() === null) {
				if (!$input_only || !in_array($name, $key_names)) {
					$required[] = $name;
				}
			}

			$properties[$name] = $prop;
		}

		$schema = array(
			'type' => 'object',
			'properties' => $properties,
		);
		if (!empty($required)) {
			$schema['required'] = $required;
		}

		return $schema;
	}

	/**
	 * Convert a DBField to an OpenAPI property schema.
	 *
	 * @param IDBField $field
	 * @return array
	 */
	public static function field_to_schema(IDBField $field): array {
		$class = get_class($field);
		$type_info = self::TYPE_MAP[$class] ?? array('type' => 'string', 'format' => null);

		$prop = array('type' => $type_info['type']);
		if ($type_info['format'] !== null) {
			$prop['format'] = $type_info['format'];
		}

		if ($field->get_null_allowed()) {
			$prop['nullable'] = true;
		}

		$default = $field->get_field_default();
		if ($default !== null) {
			$prop['default'] = $default;
		}

		// Enum values
		if ($field instanceof DBFieldEnum) {
			try {
				$ref = new ReflectionProperty($field, 'allowed');
				$ref->setAccessible(true);
				$values = $ref->getValue($field);
				if (!empty($values)) {
					$prop['enum'] = $values;
				}
			} catch (ReflectionException $e) {
				// Skip
			}
		}

		// Text length
		if ($field instanceof DBFieldText) {
			try {
				$ref = new ReflectionProperty($field, 'length');
				$ref->setAccessible(true);
				$length = $ref->getValue($field);
				if ($length > 0) {
					$prop['maxLength'] = (int)$length;
				}
			} catch (ReflectionException $e) {
				// Skip
			}
		}

		return $prop;
	}

	/**
	 * Convert a table name to a PascalCase schema name.
	 *
	 * @param string $table
	 * @return string
	 */
	public static function schema_name(string $table): string {
		$parts = preg_split('/[_\-]/', $table);
		return implode('', array_map('ucfirst', $parts));
	}

	/**
	 * Standard list endpoint query parameters.
	 *
	 * @return array
	 */
	private static function list_parameters(): array {
		return array(
			array(
				'name' => 'page',
				'in' => 'query',
				'schema' => array('type' => 'integer', 'default' => 1, 'minimum' => 1),
				'description' => 'Page number',
			),
			array(
				'name' => 'per_page',
				'in' => 'query',
				'schema' => array('type' => 'integer', 'default' => RestApiController::DEFAULT_PER_PAGE, 'maximum' => RestApiController::MAX_PER_PAGE),
				'description' => 'Items per page (max ' . RestApiController::MAX_PER_PAGE . ')',
			),
			array(
				'name' => 'sort',
				'in' => 'query',
				'schema' => array('type' => 'string'),
				'description' => 'Field name to sort by',
			),
			array(
				'name' => 'order',
				'in' => 'query',
				'schema' => array('type' => 'string', 'enum' => array('asc', 'desc'), 'default' => 'asc'),
				'description' => 'Sort direction',
			),
		);
	}

	/**
	 * Standard {id} path parameter.
	 *
	 * @param string $description
	 * @return array
	 */
	private static function id_parameter(string $description = 'id'): array {
		return array(
			'name' => 'id',
			'in' => 'path',
			'required' => true,
			'schema' => array('type' => 'string'),
			'description' => 'Record primary key (' . $description . ')',
		);
	}

	/**
	 * Standard error response object.
	 *
	 * @param string $description
	 * @return array
	 */
	private static function error_response(string $description): array {
		return array(
			'description' => $description,
			'content' => array(
				'application/json' => array(
					'schema' => array(
						'type' => 'object',
						'properties' => array(
							'error' => array('type' => 'string'),
							'details' => array('type' => 'array', 'items' => array('type' => 'string')),
						),
						'required' => array('error'),
					),
				),
			),
		);
	}
}
