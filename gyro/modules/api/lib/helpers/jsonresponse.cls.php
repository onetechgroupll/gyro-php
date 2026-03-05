<?php
/**
 * Helper class for sending JSON HTTP responses.
 *
 * @since 0.9
 * @ingroup API
 */
class JsonResponse {
	/**
	 * Send a JSON success response
	 *
	 * @param mixed $data The data to encode
	 * @param int $status_code HTTP status code
	 */
	public static function success($data, int $status_code = 200): void {
		self::send($data, $status_code);
	}

	/**
	 * Send a JSON error response
	 *
	 * @param string $message Error message
	 * @param int $status_code HTTP status code
	 * @param array $details Additional error details
	 */
	public static function error(string $message, int $status_code = 400, array $details = array()): void {
		$body = array('error' => $message);
		if (!empty($details)) {
			$body['details'] = $details;
		}
		self::send($body, $status_code);
	}

	/**
	 * Send a JSON response and exit
	 *
	 * @param mixed $data The data to encode
	 * @param int $status_code HTTP status code
	 */
	public static function send($data, int $status_code = 200): void {
		http_response_code($status_code);
		header('Content-Type: application/json; charset=utf-8');
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: no-store');
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		exit;
	}

	/**
	 * Convert a DataObjectBase instance to an associative array.
	 * Respects INTERNAL flag — internal fields are excluded.
	 *
	 * @param DataObjectBase $dao The DAO instance with data loaded
	 * @param bool $include_internal Whether to include INTERNAL fields
	 * @return array
	 */
	public static function dao_to_array(DataObjectBase $dao, bool $include_internal = false): array {
		$result = array();
		foreach ($dao->get_table_fields() as $name => $field) {
			if (!$include_internal && $field->has_policy(DBField::INTERNAL)) {
				continue;
			}
			$value = $dao->$name ?? null;
			$result[$name] = self::cast_field_value($field, $value);
		}
		return $result;
	}

	/**
	 * Cast a field value to its appropriate JSON type
	 *
	 * @param IDBField $field
	 * @param mixed $value
	 * @return mixed
	 */
	private static function cast_field_value(IDBField $field, $value) {
		if ($value === null) {
			return null;
		}

		$class = get_class($field);
		return match ($class) {
			'DBFieldInt' => (int)$value,
			'DBFieldFloat' => (float)$value,
			'DBFieldBool' => ($value === 'TRUE' || $value === true || $value === 1 || $value === '1'),
			'DBFieldSerialized' => is_string($value) ? @unserialize($value) : $value,
			default => (string)$value,
		};
	}
}
