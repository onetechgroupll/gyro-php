<?php
/**
 * HTML rendering helper for the Auto-Admin module.
 *
 * Generates self-contained HTML pages with embedded CSS for the admin interface.
 * No external dependencies — works out of the box.
 *
 * @since 0.11
 * @ingroup Admin
 */
class AdminHtml {
	/**
	 * Render a full HTML page
	 *
	 * @param string $title Page title
	 * @param string $content HTML body content
	 * @param string $nav_html Optional navigation HTML
	 * @return string
	 */
	public static function page(string $title, string $content, string $nav_html = ''): string {
		$css = self::css();
		return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . self::esc($title) . ' — Gyro Admin</title>
<style>' . $css . '</style>
</head>
<body>
<header class="admin-header">
  <a href="admin/" class="admin-logo">Gyro Admin</a>
  ' . $nav_html . '
</header>
<main class="admin-main">' . $content . '</main>
<footer class="admin-footer">Gyro-PHP Auto-Admin &middot; Generated from model schema</footer>
</body>
</html>';
	}

	/**
	 * Render navigation from model list
	 *
	 * @param array $models table_name => dao_class
	 * @param string $active_table Currently active table
	 * @return string
	 */
	public static function nav(array $models, string $active_table = ''): string {
		$html = '<nav class="admin-nav"><ul>';
		foreach ($models as $table => $class) {
			$active = ($table === $active_table) ? ' class="active"' : '';
			$html .= '<li' . $active . '><a href="admin/' . self::esc($table) . '/">' . self::esc(ucfirst($table)) . '</a></li>';
		}
		$html .= '</ul></nav>';
		return $html;
	}

	/**
	 * Render a data table
	 *
	 * @param array $headers Column headers (field_name => label)
	 * @param array $rows Array of associative arrays
	 * @param string $table_name For generating links
	 * @param array $key_names Primary key field names
	 * @return string
	 */
	public static function table(array $headers, array $rows, string $table_name, array $key_names): string {
		$html = '<table class="admin-table"><thead><tr>';
		foreach ($headers as $name => $label) {
			$html .= '<th>' . self::esc($label) . '</th>';
		}
		$html .= '<th>Actions</th></tr></thead><tbody>';

		if (empty($rows)) {
			$html .= '<tr><td colspan="' . (count($headers) + 1) . '" class="empty">No records found.</td></tr>';
		}

		foreach ($rows as $row) {
			$html .= '<tr>';
			foreach ($headers as $name => $label) {
				$value = $row[$name] ?? '';
				$html .= '<td>' . self::esc(self::truncate((string)$value, 80)) . '</td>';
			}
			// Build ID for link
			$id_parts = array();
			foreach ($key_names as $k) {
				$id_parts[] = $row[$k] ?? '';
			}
			$id = implode('|', $id_parts);
			$html .= '<td class="actions">';
			$html .= '<a href="admin/' . self::esc($table_name) . '/' . self::esc($id) . '/" class="btn btn-sm">View</a> ';
			$html .= '<a href="admin/' . self::esc($table_name) . '/' . self::esc($id) . '/edit" class="btn btn-sm btn-edit">Edit</a> ';
			$html .= '<a href="admin/' . self::esc($table_name) . '/' . self::esc($id) . '/delete" class="btn btn-sm btn-delete" onclick="return confirm(\'Delete this record?\')">Delete</a>';
			$html .= '</td></tr>';
		}
		$html .= '</tbody></table>';
		return $html;
	}

	/**
	 * Render a detail view
	 *
	 * @param array $fields field_name => array('label' => ..., 'value' => ..., 'type' => ...)
	 * @return string
	 */
	public static function detail(array $fields): string {
		$html = '<dl class="admin-detail">';
		foreach ($fields as $name => $info) {
			$html .= '<dt>' . self::esc($info['label']) . '</dt>';
			$html .= '<dd>' . self::esc((string)($info['value'] ?? '')) . '</dd>';
		}
		$html .= '</dl>';
		return $html;
	}

	/**
	 * Render a form for create/edit
	 *
	 * @param array $fields field_name => array('label', 'type', 'value', 'required', 'options', 'maxlength')
	 * @param string $action Form action URL
	 * @param string $method Form method (POST)
	 * @param string $submit_label
	 * @return string
	 */
	public static function form(array $fields, string $action, string $method = 'POST', string $submit_label = 'Save'): string {
		$html = '<form method="' . self::esc($method) . '" action="' . self::esc($action) . '" class="admin-form">';

		foreach ($fields as $name => $info) {
			$label = $info['label'] ?? $name;
			$type = $info['type'] ?? 'text';
			$value = $info['value'] ?? '';
			$required = !empty($info['required']) ? ' required' : '';
			$maxlength = isset($info['maxlength']) ? ' maxlength="' . (int)$info['maxlength'] . '"' : '';

			$html .= '<div class="form-group">';
			$html .= '<label for="field_' . self::esc($name) . '">' . self::esc($label);
			if (!empty($info['required'])) {
				$html .= ' <span class="required">*</span>';
			}
			$html .= '</label>';

			if ($type === 'textarea') {
				$html .= '<textarea id="field_' . self::esc($name) . '" name="' . self::esc($name) . '"' . $required . '>' . self::esc((string)$value) . '</textarea>';
			} elseif ($type === 'select' && isset($info['options'])) {
				$html .= '<select id="field_' . self::esc($name) . '" name="' . self::esc($name) . '"' . $required . '>';
				foreach ($info['options'] as $opt) {
					$selected = ((string)$opt === (string)$value) ? ' selected' : '';
					$html .= '<option value="' . self::esc($opt) . '"' . $selected . '>' . self::esc($opt) . '</option>';
				}
				$html .= '</select>';
			} elseif ($type === 'checkbox') {
				$checked = ($value === true || $value === 'TRUE' || $value === '1' || $value === 1) ? ' checked' : '';
				$html .= '<input type="checkbox" id="field_' . self::esc($name) . '" name="' . self::esc($name) . '" value="1"' . $checked . '>';
			} else {
				$input_type = $type;
				if ($type === 'integer') {
					$input_type = 'number';
				} elseif ($type === 'float') {
					$input_type = 'number" step="any';
				}
				$html .= '<input type="' . $input_type . '" id="field_' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . self::esc((string)$value) . '"' . $required . $maxlength . '>';
			}
			$html .= '</div>';
		}

		$html .= '<div class="form-actions"><button type="submit" class="btn btn-primary">' . self::esc($submit_label) . '</button></div>';
		$html .= '</form>';
		return $html;
	}

	/**
	 * Render pagination
	 *
	 * @param int $page Current page
	 * @param int $total_pages
	 * @param string $base_url URL with {page} placeholder
	 * @return string
	 */
	public static function pager(int $page, int $total_pages, string $base_url): string {
		if ($total_pages <= 1) {
			return '';
		}
		$html = '<div class="admin-pager">';
		if ($page > 1) {
			$html .= '<a href="' . str_replace('{page}', (string)($page - 1), $base_url) . '" class="btn btn-sm">&laquo; Prev</a> ';
		}
		$html .= '<span class="pager-info">Page ' . $page . ' of ' . $total_pages . '</span>';
		if ($page < $total_pages) {
			$html .= ' <a href="' . str_replace('{page}', (string)($page + 1), $base_url) . '" class="btn btn-sm">Next &raquo;</a>';
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * Render an alert/message box
	 *
	 * @param string $message
	 * @param string $type success|error|warning|info
	 * @return string
	 */
	public static function alert(string $message, string $type = 'info'): string {
		return '<div class="admin-alert admin-alert-' . self::esc($type) . '">' . self::esc($message) . '</div>';
	}

	/**
	 * HTML-escape a string
	 *
	 * @param string $str
	 * @return string
	 */
	public static function esc(string $str): string {
		return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	/**
	 * Truncate string to max length
	 *
	 * @param string $str
	 * @param int $max
	 * @return string
	 */
	public static function truncate(string $str, int $max): string {
		if (mb_strlen($str) <= $max) {
			return $str;
		}
		return mb_substr($str, 0, $max - 3) . '...';
	}

	/**
	 * Map a DBField to an HTML form input type
	 *
	 * @param IDBField $field
	 * @return string
	 */
	public static function field_to_input_type(IDBField $field): string {
		$class = get_class($field);
		return match ($class) {
			'DBFieldInt' => 'integer',
			'DBFieldFloat' => 'float',
			'DBFieldBool' => 'checkbox',
			'DBFieldDate' => 'date',
			'DBFieldDateTime' => 'datetime-local',
			'DBFieldTime' => 'time',
			'DBFieldEnum' => 'select',
			'DBFieldSet' => 'select',
			'DBFieldBlob' => 'textarea',
			'DBFieldSerialized' => 'textarea',
			'DBFieldText' => self::text_field_input_type($field),
			default => 'text',
		};
	}

	/**
	 * Determine input type for text fields based on length
	 *
	 * @param IDBField $field
	 * @return string
	 */
	private static function text_field_input_type(IDBField $field): string {
		try {
			$ref = new ReflectionProperty($field, 'length');
			$ref->setAccessible(true);
			$length = (int)$ref->getValue($field);
			if ($length > 255) {
				return 'textarea';
			}
		} catch (ReflectionException $e) {
			// Fall through
		}
		return 'text';
	}

	/**
	 * Embedded CSS for the admin interface
	 *
	 * @return string
	 */
	private static function css(): string {
		return '
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f7fa; color: #333; line-height: 1.6; }
a { color: #2563eb; text-decoration: none; }
a:hover { text-decoration: underline; }
.admin-header { background: #1e293b; color: #fff; padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 2rem; }
.admin-logo { color: #fff; font-size: 1.25rem; font-weight: 700; }
.admin-logo:hover { text-decoration: none; color: #93c5fd; }
.admin-nav ul { list-style: none; display: flex; gap: 0.25rem; flex-wrap: wrap; }
.admin-nav li a { color: #cbd5e1; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.875rem; display: block; }
.admin-nav li a:hover { background: #334155; color: #fff; text-decoration: none; }
.admin-nav li.active a { background: #2563eb; color: #fff; }
.admin-main { max-width: 1200px; margin: 1.5rem auto; padding: 0 1rem; }
.admin-footer { text-align: center; padding: 2rem; color: #94a3b8; font-size: 0.8rem; }
h1 { font-size: 1.5rem; margin-bottom: 1rem; }
h2 { font-size: 1.25rem; margin-bottom: 0.75rem; }
.admin-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.admin-table th { background: #f1f5f9; padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; }
.admin-table td { padding: 0.625rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
.admin-table tr:hover td { background: #f8fafc; }
.admin-table .empty { text-align: center; color: #94a3b8; padding: 2rem; }
.admin-table .actions { white-space: nowrap; }
.btn { display: inline-block; padding: 0.375rem 0.75rem; border-radius: 4px; font-size: 0.875rem; border: 1px solid #d1d5db; background: #fff; cursor: pointer; color: #374151; }
.btn:hover { background: #f9fafb; text-decoration: none; }
.btn-sm { padding: 0.2rem 0.5rem; font-size: 0.8rem; }
.btn-primary { background: #2563eb; color: #fff; border-color: #2563eb; }
.btn-primary:hover { background: #1d4ed8; color: #fff; }
.btn-edit { border-color: #f59e0b; color: #92400e; }
.btn-edit:hover { background: #fffbeb; }
.btn-delete { border-color: #ef4444; color: #dc2626; }
.btn-delete:hover { background: #fef2f2; }
.admin-detail { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.admin-detail dt { font-weight: 600; color: #64748b; font-size: 0.8rem; text-transform: uppercase; margin-top: 1rem; }
.admin-detail dt:first-child { margin-top: 0; }
.admin-detail dd { font-size: 0.95rem; padding: 0.25rem 0; }
.admin-form { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 640px; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.9rem; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem; }
.form-group input[type="checkbox"] { width: auto; }
.form-group textarea { min-height: 100px; resize: vertical; }
.form-group .required { color: #dc2626; }
.form-actions { margin-top: 1.5rem; }
.admin-pager { margin: 1rem 0; display: flex; align-items: center; gap: 0.5rem; }
.pager-info { color: #64748b; font-size: 0.9rem; }
.admin-alert { padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem; }
.admin-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.admin-alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.admin-alert-warning { background: #fffbeb; color: #92400e; border: 1px solid #fed7aa; }
.admin-alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.admin-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.admin-breadcrumb { font-size: 0.9rem; color: #64748b; margin-bottom: 0.5rem; }
.admin-breadcrumb a { color: #2563eb; }
.admin-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.admin-stat { background: #fff; padding: 1.25rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.admin-stat .label { font-size: 0.8rem; color: #64748b; text-transform: uppercase; }
.admin-stat .value { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
';
	}
}
