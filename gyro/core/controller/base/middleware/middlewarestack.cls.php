<?php
/**
 * Manages global and per-route middleware
 *
 * Middleware is executed in the order it was registered.
 * Global middleware runs on every request.
 * Route middleware runs only for matching routes.
 *
 * Usage:
 *   // Register global middleware (in start.inc.php)
 *   MiddlewareStack::add(new CorsMiddleware());
 *   MiddlewareStack::add(new LoggingMiddleware());
 *
 *   // Register with priority (lower = earlier, default 100)
 *   MiddlewareStack::add(new AuthMiddleware(), 50);
 *
 * @author Gyro-PHP Framework
 * @ingroup Controller
 */
class MiddlewareStack {
	/**
	 * Registered global middleware
	 *
	 * @var array Array of ['middleware' => IMiddleware, 'priority' => int]
	 */
	private static $middleware = array();

	/**
	 * Whether the stack has been sorted
	 *
	 * @var bool
	 */
	private static $sorted = true;

	/**
	 * Register a global middleware
	 *
	 * @param IMiddleware $middleware The middleware to register
	 * @param int $priority Execution priority (lower = earlier, default 100)
	 * @return void
	 */
	public static function add($middleware, $priority = 100) {
		self::$middleware[] = array(
			'middleware' => $middleware,
			'priority' => $priority
		);
		self::$sorted = false;
	}

	/**
	 * Get all registered global middleware, sorted by priority
	 *
	 * @return IMiddleware[]
	 */
	public static function get_all() {
		if (!self::$sorted) {
			usort(self::$middleware, function($a, $b) {
				return $a['priority'] - $b['priority'];
			});
			self::$sorted = true;
		}
		return array_map(function($entry) {
			return $entry['middleware'];
		}, self::$middleware);
	}

	/**
	 * Remove all registered middleware (primarily for testing)
	 *
	 * @return void
	 */
	public static function clear() {
		self::$middleware = array();
		self::$sorted = true;
	}

	/**
	 * Get the count of registered middleware
	 *
	 * @return int
	 */
	public static function count() {
		return count(self::$middleware);
	}
}
