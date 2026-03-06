<?php
/**
 * Simple Dependency Injection Container
 *
 * Provides service registration and lazy resolution. Supports:
 * - Singleton services (resolved once, reused)
 * - Factory services (new instance each time)
 * - Instance binding (pre-created objects)
 *
 * Usage:
 *   // Register a singleton (created on first get())
 *   $container = Container::instance();
 *   $container->singleton('logger', function() {
 *       return new MyLogger('/var/log/app.log');
 *   });
 *
 *   // Register a factory (new instance each time)
 *   $container->factory('mailer', function($c) {
 *       return new Mailer($c->get('config'));
 *   });
 *
 *   // Bind an existing instance
 *   $container->instance('config', Config::class);
 *
 *   // Resolve
 *   $logger = $container->get('logger');
 *
 *   // Static shortcut
 *   $logger = Container::get_service('logger');
 *
 * @author Gyro-PHP Framework
 * @ingroup Components
 */
class Container {
	/**
	 * The singleton container instance
	 *
	 * @var Container|null
	 */
	private static $container = null;

	/**
	 * Service definitions (closures for lazy loading)
	 *
	 * @var array
	 */
	private $definitions = array();

	/**
	 * Resolved singleton instances
	 *
	 * @var array
	 */
	private $instances = array();

	/**
	 * Service types ('singleton' or 'factory')
	 *
	 * @var array
	 */
	private $types = array();

	/**
	 * Get the global container instance
	 *
	 * @return Container
	 */
	public static function instance() {
		if (self::$container === null) {
			self::$container = new self();
		}
		return self::$container;
	}

	/**
	 * Static shortcut to get a service from the global container
	 *
	 * @param string $name Service name
	 * @return mixed The resolved service
	 */
	public static function get_service($name) {
		return self::instance()->get($name);
	}

	/**
	 * Register a singleton service (resolved once, then reused)
	 *
	 * @param string $name Service name
	 * @param callable $factory Factory function that receives the Container
	 * @return void
	 */
	public function singleton($name, $factory) {
		$this->definitions[$name] = $factory;
		$this->types[$name] = 'singleton';
		unset($this->instances[$name]);
	}

	/**
	 * Register a factory service (new instance on each get())
	 *
	 * @param string $name Service name
	 * @param callable $factory Factory function that receives the Container
	 * @return void
	 */
	public function factory($name, $factory) {
		$this->definitions[$name] = $factory;
		$this->types[$name] = 'factory';
	}

	/**
	 * Bind a pre-created instance directly
	 *
	 * @param string $name Service name
	 * @param mixed $instance The object to bind
	 * @return void
	 */
	public function bind($name, $instance) {
		$this->instances[$name] = $instance;
		$this->types[$name] = 'singleton';
		unset($this->definitions[$name]);
	}

	/**
	 * Resolve a service by name
	 *
	 * @param string $name Service name
	 * @return mixed The resolved service
	 * @throws Exception if service is not registered
	 */
	public function get($name) {
		// Return cached singleton instance
		if (isset($this->instances[$name])) {
			return $this->instances[$name];
		}

		// Resolve from definition
		if (!isset($this->definitions[$name])) {
			throw new Exception("Service '$name' is not registered in the container.");
		}

		$resolved = call_user_func($this->definitions[$name], $this);

		// Cache singletons
		if (isset($this->types[$name]) && $this->types[$name] === 'singleton') {
			$this->instances[$name] = $resolved;
		}

		return $resolved;
	}

	/**
	 * Check if a service is registered
	 *
	 * @param string $name Service name
	 * @return bool
	 */
	public function has($name) {
		return isset($this->definitions[$name]) || isset($this->instances[$name]);
	}

	/**
	 * Get all registered service names
	 *
	 * @return string[]
	 */
	public function get_service_names() {
		return array_unique(array_merge(
			array_keys($this->definitions),
			array_keys($this->instances)
		));
	}

	/**
	 * Reset the container (primarily for testing)
	 *
	 * @return void
	 */
	public function reset() {
		$this->definitions = array();
		$this->instances = array();
		$this->types = array();
	}

	/**
	 * Reset the global container instance (primarily for testing)
	 *
	 * @return void
	 */
	public static function reset_instance() {
		if (self::$container !== null) {
			self::$container->reset();
		}
		self::$container = null;
	}
}
