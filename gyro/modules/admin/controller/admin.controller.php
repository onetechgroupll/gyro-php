<?php
/**
 * Auto-Admin Controller.
 *
 * Django-Admin-Style CRUD interface auto-generated from DAO model schemas.
 * Uses ISelfDescribingType for labels, IActionSource for actions,
 * and DBField introspection for form generation.
 *
 * Routes:
 *   GET  /admin/                         — Dashboard (model overview)
 *   GET  /admin/{table}/                 — List records
 *   GET  /admin/{table}/create           — Create form
 *   POST /admin/{table}/create           — Create record
 *   GET  /admin/{table}/{id}/            — Detail view
 *   GET  /admin/{table}/{id}/edit        — Edit form
 *   POST /admin/{table}/{id}/edit        — Update record
 *   GET  /admin/{table}/{id}/delete      — Confirm delete
 *   POST /admin/{table}/{id}/delete      — Execute delete
 *
 * @since 0.11
 * @ingroup Admin
 */
class AdminController extends ControllerBase {
	/**
	 * Items per page in list view
	 */
	const PER_PAGE = 25;

	/**
	 * Registry of admin-enabled tables
	 *
	 * @var array table_name => dao_class
	 */
	private static $models = array();

	/**
	 * Excluded tables
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
	 * Register routes
	 *
	 * @return array
	 */
	public function get_routes() {
		return array(
			new ExactMatchRoute('admin/', $this, 'admin_dashboard', null),
			new RouteBase('admin/', $this, 'admin_dispatch', null),
		);
	}

	// -------------------------------------------------------
	// Static configuration
	// -------------------------------------------------------

	/**
	 * Register a model for the admin UI
	 *
	 * @param string $table_name
	 * @param string $dao_class
	 */
	public static function register_model(string $table_name, string $dao_class): void {
		self::$models[$table_name] = $dao_class;
	}

	/**
	 * Exclude a table from auto-discovery
	 *
	 * @param string $table_name
	 */
	public static function exclude_table(string $table_name): void {
		self::$excluded_tables[$table_name] = true;
	}

	/**
	 * Get all registered models
	 *
	 * @return array
	 */
	public static function get_registered_models(): array {
		self::ensure_discovery();
		return self::$models;
	}

	/**
	 * Discover models if not already done
	 */
	private static function ensure_discovery(): void {
		if (self::$discovered) {
			return;
		}
		self::$discovered = true;

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
	 * GET /admin/ — Dashboard
	 */
	public function action_admin_dashboard($page_data) {
		$models = self::get_registered_models();
		$nav = AdminHtml::nav($models);

		$content = '<h1>Dashboard</h1>';
		$content .= '<div class="admin-stats">';
		$content .= '<div class="admin-stat"><div class="label">Models</div><div class="value">' . count($models) . '</div></div>';
		$content .= '</div>';

		$content .= '<h2>Registered Models</h2>';
		$content .= '<table class="admin-table"><thead><tr><th>Table</th><th>DAO Class</th><th>Fields</th><th>Actions</th></tr></thead><tbody>';
		foreach ($models as $table => $class) {
			try {
				$dao = new $class();
				$field_count = count($dao->get_table_fields());
			} catch (\Throwable $e) {
				$field_count = '?';
			}
			$content .= '<tr>';
			$content .= '<td><strong>' . AdminHtml::esc($table) . '</strong></td>';
			$content .= '<td><code>' . AdminHtml::esc($class) . '</code></td>';
			$content .= '<td>' . $field_count . '</td>';
			$content .= '<td><a href="admin/' . AdminHtml::esc($table) . '/" class="btn btn-sm">Manage</a></td>';
			$content .= '</tr>';
		}
		$content .= '</tbody></table>';

		$this->send_html(AdminHtml::page('Dashboard', $content, $nav));
	}

	/**
	 * Dispatch admin sub-routes
	 */
	public function action_admin_dispatch($page_data) {
		$pathstack = $page_data->get_pathstack();
		$table_name = $pathstack->shift();

		if (empty($table_name)) {
			$this->send_html(AdminHtml::page('Not Found', '<h1>404</h1><p>No table specified.</p>'), 404);
			return;
		}

		$models = self::get_registered_models();
		if (!isset($models[$table_name])) {
			$this->send_html(AdminHtml::page('Not Found', '<h1>404</h1><p>Unknown model: ' . AdminHtml::esc($table_name) . '</p>'), 404);
			return;
		}

		$dao_class = $models[$table_name];
		$id_or_action = $pathstack->shift();
		$method = strtoupper(Arr::get_item($_SERVER, 'REQUEST_METHOD', 'GET'));

		// GET /admin/{table}/ — list
		if (empty($id_or_action)) {
			$this->handle_list($table_name, $dao_class, $page_data);
			return;
		}

		// GET/POST /admin/{table}/create — create
		if ($id_or_action === 'create') {
			if ($method === 'POST') {
				$this->handle_create_post($table_name, $dao_class);
			} else {
				$this->handle_create_form($table_name, $dao_class);
			}
			return;
		}

		// /admin/{table}/{id}/...
		$sub_action = $pathstack->shift();

		if ($sub_action === 'edit') {
			if ($method === 'POST') {
				$this->handle_edit_post($table_name, $dao_class, $id_or_action);
			} else {
				$this->handle_edit_form($table_name, $dao_class, $id_or_action);
			}
		} elseif ($sub_action === 'delete') {
			if ($method === 'POST') {
				$this->handle_delete_post($table_name, $dao_class, $id_or_action);
			} else {
				$this->handle_delete_confirm($table_name, $dao_class, $id_or_action);
			}
		} else {
			// GET /admin/{table}/{id}/ — detail
			$this->handle_detail($table_name, $dao_class, $id_or_action);
		}
	}

	// -------------------------------------------------------
	// List
	// -------------------------------------------------------

	/**
	 * GET /admin/{table}/ — List records with paging
	 */
	protected function handle_list(string $table_name, string $dao_class, $page_data): void {
		$models = self::get_registered_models();
		$nav = AdminHtml::nav($models, $table_name);

		$dao = new $dao_class();
		/** @var DataObjectBase $dao */

		$page = max(1, (int)Arr::get_item($_GET, 'page', 1));
		$per_page = self::PER_PAGE;
		$offset = ($page - 1) * $per_page;

		// Sorting
		$sort_field = Arr::get_item($_GET, 'sort', '');
		$sort_order = strtolower(Arr::get_item($_GET, 'order', 'asc'));
		$fields = $dao->get_table_fields();
		if ($sort_field !== '' && isset($fields[$sort_field])) {
			$dao->sort($sort_field, $sort_order === 'desc' ? DataObjectBase::DESC : DataObjectBase::ASC);
		}

		// Count
		$count_dao = clone $dao;
		$total = $count_dao->count();
		$total_pages = $total > 0 ? (int)ceil($total / $per_page) : 0;

		// Fetch
		$dao->limit($offset, $per_page);
		$items = $dao->find_array();

		// Build display headers (max 6 visible columns)
		$headers = $this->get_list_headers($dao, 6);
		$key_names = array_keys($dao->get_table_keys());

		// Build rows
		$rows = array();
		foreach ($items as $item) {
			$row = array();
			foreach ($headers as $name => $label) {
				$row[$name] = $item->$name ?? '';
			}
			// Always include keys in row data for link generation
			foreach ($key_names as $k) {
				if (!isset($row[$k])) {
					$row[$k] = $item->$k ?? '';
				}
			}
			$rows[] = $row;
		}

		$type_name = ucfirst($table_name);
		if ($dao instanceof ISelfDescribingType) {
			$plural = $dao->get_type_name_plural();
			if (!empty($plural)) {
				$type_name = ucfirst($plural);
			}
		}

		$breadcrumb = '<div class="admin-breadcrumb"><a href="admin/">Admin</a> &rsaquo; ' . AdminHtml::esc($type_name) . '</div>';
		$toolbar = '<div class="admin-toolbar"><h1>' . AdminHtml::esc($type_name) . ' <small style="color:#94a3b8;font-weight:normal">(' . $total . ' records)</small></h1>';
		$toolbar .= '<a href="admin/' . AdminHtml::esc($table_name) . '/create" class="btn btn-primary">+ New Record</a></div>';

		$content = $breadcrumb . $toolbar;

		// Flash message
		$flash = Arr::get_item($_GET, 'msg', '');
		if ($flash === 'created') {
			$content .= AdminHtml::alert('Record created successfully.', 'success');
		} elseif ($flash === 'updated') {
			$content .= AdminHtml::alert('Record updated successfully.', 'success');
		} elseif ($flash === 'deleted') {
			$content .= AdminHtml::alert('Record deleted successfully.', 'success');
		}

		$content .= AdminHtml::table($headers, $rows, $table_name, $key_names);
		$pager_url = 'admin/' . $table_name . '/?page={page}';
		$content .= AdminHtml::pager($page, $total_pages, $pager_url);

		$this->send_html(AdminHtml::page($type_name, $content, $nav));
	}

	// -------------------------------------------------------
	// Detail
	// -------------------------------------------------------

	/**
	 * GET /admin/{table}/{id}/ — Detail view
	 */
	protected function handle_detail(string $table_name, string $dao_class, string $id): void {
		$models = self::get_registered_models();
		$nav = AdminHtml::nav($models, $table_name);

		$dao = $this->find_record($dao_class, $id);
		if ($dao === false) {
			$this->send_html(AdminHtml::page('Not Found', '<h1>Record Not Found</h1>', $nav), 404);
			return;
		}

		$fields_info = array();
		foreach ($dao->get_table_fields() as $name => $field) {
			if ($field->has_policy(DBField::INTERNAL)) {
				continue;
			}
			$fields_info[$name] = array(
				'label' => ucfirst(str_replace('_', ' ', $name)),
				'value' => $dao->$name ?? null,
				'type' => AdminHtml::field_to_input_type($field),
			);
		}

		$type_name = ucfirst($table_name);
		$breadcrumb = '<div class="admin-breadcrumb"><a href="admin/">Admin</a> &rsaquo; <a href="admin/' . AdminHtml::esc($table_name) . '/">' . AdminHtml::esc($type_name) . '</a> &rsaquo; #' . AdminHtml::esc($id) . '</div>';
		$toolbar = '<div class="admin-toolbar"><h1>' . AdminHtml::esc($type_name) . ' #' . AdminHtml::esc($id) . '</h1>';
		$toolbar .= '<div>';
		$toolbar .= '<a href="admin/' . AdminHtml::esc($table_name) . '/' . AdminHtml::esc($id) . '/edit" class="btn btn-edit">Edit</a> ';
		$toolbar .= '<a href="admin/' . AdminHtml::esc($table_name) . '/' . AdminHtml::esc($id) . '/delete" class="btn btn-delete">Delete</a>';
		$toolbar .= '</div></div>';

		$content = $breadcrumb . $toolbar . AdminHtml::detail($fields_info);

		$this->send_html(AdminHtml::page($type_name . ' #' . $id, $content, $nav));
	}

	// -------------------------------------------------------
	// Create
	// -------------------------------------------------------

	/**
	 * GET /admin/{table}/create — Show create form
	 */
	protected function handle_create_form(string $table_name, string $dao_class): void {
		$models = self::get_registered_models();
		$nav = AdminHtml::nav($models, $table_name);

		$dao = new $dao_class();
		/** @var DataObjectBase $dao */
		$dao->set_default_values();
		$form_fields = $this->build_form_fields($dao, true);

		$type_name = ucfirst($table_name);
		$breadcrumb = '<div class="admin-breadcrumb"><a href="admin/">Admin</a> &rsaquo; <a href="admin/' . AdminHtml::esc($table_name) . '/">' . AdminHtml::esc($type_name) . '</a> &rsaquo; New</div>';
		$content = $breadcrumb . '<h1>New ' . AdminHtml::esc($type_name) . '</h1>';
		$content .= AdminHtml::form($form_fields, 'admin/' . $table_name . '/create', 'POST', 'Create');

		$this->send_html(AdminHtml::page('New ' . $type_name, $content, $nav));
	}

	/**
	 * POST /admin/{table}/create — Process create
	 */
	protected function handle_create_post(string $table_name, string $dao_class): void {
		$models = self::get_registered_models();
		$nav = AdminHtml::nav($models, $table_name);

		$dao = new $dao_class();
		/** @var DataObjectBase $dao */
		$dao->set_default_values();

		$cleaned = $dao->unset_internals($_POST);
		$dao->read_from_array($cleaned);

		$status = $dao->validate();
		if (!$status->is_ok()) {
			$form_fields = $this->build_form_fields($dao, true);
			$type_name = ucfirst($table_name);
			$content = '<h1>New ' . AdminHtml::esc($type_name) . '</h1>';
			$content .= AdminHtml::alert('Validation failed: ' . $this->status_message($status), 'error');
			$content .= AdminHtml::form($form_fields, 'admin/' . $table_name . '/create', 'POST', 'Create');
			$this->send_html(AdminHtml::page('New ' . $type_name, $content, $nav));
			return;
		}

		$status = $dao->insert();
		if (!$status->is_ok()) {
			$form_fields = $this->build_form_fields($dao, true);
			$type_name = ucfirst($table_name);
			$content = '<h1>New ' . AdminHtml::esc($type_name) . '</h1>';
			$content .= AdminHtml::alert('Insert failed: ' . $this->status_message($status), 'error');
			$content .= AdminHtml::form($form_fields, 'admin/' . $table_name . '/create', 'POST', 'Create');
			$this->send_html(AdminHtml::page('New ' . $type_name, $content, $nav));
			return;
		}

		// Redirect to list with success message
		header('Location: admin/' . $table_name . '/?msg=created');
		exit;
	}

	// -------------------------------------------------------
	// Edit
	// -------------------------------------------------------

	/**
	 * GET /admin/{table}/{id}/edit — Show edit form
	 */
	protected function handle_edit_form(string $table_name, string $dao_class, string $id): void {
		$models = self::get_registered_models();
		$nav = AdminHtml::nav($models, $table_name);

		$dao = $this->find_record($dao_class, $id);
		if ($dao === false) {
			$this->send_html(AdminHtml::page('Not Found', '<h1>Record Not Found</h1>', $nav), 404);
			return;
		}

		$form_fields = $this->build_form_fields($dao, false);
		$type_name = ucfirst($table_name);
		$breadcrumb = '<div class="admin-breadcrumb"><a href="admin/">Admin</a> &rsaquo; <a href="admin/' . AdminHtml::esc($table_name) . '/">' . AdminHtml::esc($type_name) . '</a> &rsaquo; <a href="admin/' . AdminHtml::esc($table_name) . '/' . AdminHtml::esc($id) . '/">#' . AdminHtml::esc($id) . '</a> &rsaquo; Edit</div>';
		$content = $breadcrumb . '<h1>Edit ' . AdminHtml::esc($type_name) . ' #' . AdminHtml::esc($id) . '</h1>';
		$content .= AdminHtml::form($form_fields, 'admin/' . $table_name . '/' . $id . '/edit', 'POST', 'Save Changes');

		$this->send_html(AdminHtml::page('Edit ' . $type_name . ' #' . $id, $content, $nav));
	}

	/**
	 * POST /admin/{table}/{id}/edit — Process edit
	 */
	protected function handle_edit_post(string $table_name, string $dao_class, string $id): void {
		$models = self::get_registered_models();
		$nav = AdminHtml::nav($models, $table_name);

		$dao = $this->find_record($dao_class, $id);
		if ($dao === false) {
			$this->send_html(AdminHtml::page('Not Found', '<h1>Record Not Found</h1>', $nav), 404);
			return;
		}

		$cleaned = $dao->unset_internals($_POST);
		$dao->read_from_array($cleaned);

		$status = $dao->validate();
		if (!$status->is_ok()) {
			$form_fields = $this->build_form_fields($dao, false);
			$type_name = ucfirst($table_name);
			$content = '<h1>Edit ' . AdminHtml::esc($type_name) . ' #' . AdminHtml::esc($id) . '</h1>';
			$content .= AdminHtml::alert('Validation failed: ' . $this->status_message($status), 'error');
			$content .= AdminHtml::form($form_fields, 'admin/' . $table_name . '/' . $id . '/edit', 'POST', 'Save Changes');
			$this->send_html(AdminHtml::page('Edit ' . $type_name . ' #' . $id, $content, $nav));
			return;
		}

		$status = $dao->update();
		if (!$status->is_ok()) {
			$form_fields = $this->build_form_fields($dao, false);
			$type_name = ucfirst($table_name);
			$content = '<h1>Edit ' . AdminHtml::esc($type_name) . ' #' . AdminHtml::esc($id) . '</h1>';
			$content .= AdminHtml::alert('Update failed: ' . $this->status_message($status), 'error');
			$content .= AdminHtml::form($form_fields, 'admin/' . $table_name . '/' . $id . '/edit', 'POST', 'Save Changes');
			$this->send_html(AdminHtml::page('Edit ' . $type_name . ' #' . $id, $content, $nav));
			return;
		}

		header('Location: admin/' . $table_name . '/?msg=updated');
		exit;
	}

	// -------------------------------------------------------
	// Delete
	// -------------------------------------------------------

	/**
	 * GET /admin/{table}/{id}/delete — Confirm delete
	 */
	protected function handle_delete_confirm(string $table_name, string $dao_class, string $id): void {
		$models = self::get_registered_models();
		$nav = AdminHtml::nav($models, $table_name);

		$dao = $this->find_record($dao_class, $id);
		if ($dao === false) {
			$this->send_html(AdminHtml::page('Not Found', '<h1>Record Not Found</h1>', $nav), 404);
			return;
		}

		$type_name = ucfirst($table_name);
		$content = '<h1>Delete ' . AdminHtml::esc($type_name) . ' #' . AdminHtml::esc($id) . '</h1>';
		$content .= AdminHtml::alert('Are you sure you want to delete this record? This action cannot be undone.', 'warning');
		$content .= '<form method="POST" action="admin/' . AdminHtml::esc($table_name) . '/' . AdminHtml::esc($id) . '/delete">';
		$content .= '<button type="submit" class="btn btn-delete">Yes, Delete</button> ';
		$content .= '<a href="admin/' . AdminHtml::esc($table_name) . '/' . AdminHtml::esc($id) . '/" class="btn">Cancel</a>';
		$content .= '</form>';

		$this->send_html(AdminHtml::page('Delete ' . $type_name . ' #' . $id, $content, $nav));
	}

	/**
	 * POST /admin/{table}/{id}/delete — Execute delete
	 */
	protected function handle_delete_post(string $table_name, string $dao_class, string $id): void {
		$models = self::get_registered_models();
		$nav = AdminHtml::nav($models, $table_name);

		$dao = $this->find_record($dao_class, $id);
		if ($dao === false) {
			$this->send_html(AdminHtml::page('Not Found', '<h1>Record Not Found</h1>', $nav), 404);
			return;
		}

		$status = $dao->delete();
		if (!$status->is_ok()) {
			$content = '<h1>Delete Failed</h1>';
			$content .= AdminHtml::alert('Delete failed: ' . $this->status_message($status), 'error');
			$content .= '<a href="admin/' . AdminHtml::esc($table_name) . '/" class="btn">Back to List</a>';
			$this->send_html(AdminHtml::page('Delete Failed', $content, $nav));
			return;
		}

		header('Location: admin/' . $table_name . '/?msg=deleted');
		exit;
	}

	// -------------------------------------------------------
	// Helpers
	// -------------------------------------------------------

	/**
	 * Find a record by primary key
	 *
	 * @param string $dao_class
	 * @param string $id
	 * @return DataObjectBase|false
	 */
	protected function find_record(string $dao_class, string $id) {
		$dao = new $dao_class();
		/** @var DataObjectBase $dao */
		$keys = $dao->get_table_keys();
		$key_names = array_keys($keys);

		if (count($key_names) === 1) {
			$found = $dao->get($key_names[0], $id);
		} else {
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
	 * Get column headers for list view (limited to $max fields)
	 *
	 * @param DataObjectBase $dao
	 * @param int $max
	 * @return array field_name => label
	 */
	protected function get_list_headers(DataObjectBase $dao, int $max): array {
		$headers = array();
		$key_names = array_keys($dao->get_table_keys());

		// Always show primary keys first
		foreach ($key_names as $k) {
			$headers[$k] = ucfirst(str_replace('_', ' ', $k));
		}

		foreach ($dao->get_table_fields() as $name => $field) {
			if (count($headers) >= $max) {
				break;
			}
			if (isset($headers[$name])) {
				continue;
			}
			if ($field->has_policy(DBField::INTERNAL)) {
				continue;
			}
			// Skip large text fields in list view
			if ($field instanceof DBFieldBlob || $field instanceof DBFieldSerialized) {
				continue;
			}
			$headers[$name] = ucfirst(str_replace('_', ' ', $name));
		}

		return $headers;
	}

	/**
	 * Build form field definitions from a DAO
	 *
	 * @param DataObjectBase $dao
	 * @param bool $is_create If true, skip auto-increment PKs
	 * @return array
	 */
	protected function build_form_fields(DataObjectBase $dao, bool $is_create): array {
		$fields = array();
		$key_names = array_keys($dao->get_table_keys());

		foreach ($dao->get_table_fields() as $name => $field) {
			if ($field->has_policy(DBField::INTERNAL)) {
				continue;
			}

			// Skip auto-increment PKs on create
			if ($is_create && in_array($name, $key_names)) {
				$flags = $field->get_policy();
				if ($flags & DBFieldInt::AUTOINCREMENT) {
					continue;
				}
			}

			// Skip PKs on edit (read-only)
			if (!$is_create && in_array($name, $key_names)) {
				continue;
			}

			$input_type = AdminHtml::field_to_input_type($field);
			$info = array(
				'label' => ucfirst(str_replace('_', ' ', $name)),
				'type' => $input_type,
				'value' => $dao->$name ?? $field->get_field_default(),
				'required' => !$field->get_null_allowed() && $field->get_field_default() === null,
			);

			// Enum options
			if ($field instanceof DBFieldEnum) {
				try {
					$ref = new ReflectionProperty($field, 'allowed');
					$ref->setAccessible(true);
					$info['options'] = $ref->getValue($field);
				} catch (ReflectionException $e) {
					// Skip
				}
			}

			// Text max length
			if ($field instanceof DBFieldText) {
				try {
					$ref = new ReflectionProperty($field, 'length');
					$ref->setAccessible(true);
					$length = (int)$ref->getValue($field);
					if ($length > 0) {
						$info['maxlength'] = $length;
					}
				} catch (ReflectionException $e) {
					// Skip
				}
			}

			$fields[$name] = $info;
		}

		return $fields;
	}

	/**
	 * Extract a status message
	 *
	 * @param Status $status
	 * @return string
	 */
	protected function status_message(Status $status): string {
		if (method_exists($status, 'get_messages')) {
			$messages = $status->get_messages();
			if (!empty($messages)) {
				return implode(', ', array_map('strval', $messages));
			}
		}
		if (method_exists($status, 'to_string')) {
			return $status->to_string();
		}
		return 'Unknown error';
	}

	/**
	 * Send an HTML response
	 *
	 * @param string $html
	 * @param int $status_code
	 */
	protected function send_html(string $html, int $status_code = 200): void {
		http_response_code($status_code);
		header('Content-Type: text/html; charset=utf-8');
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: no-store');
		echo $html;
		exit;
	}
}
