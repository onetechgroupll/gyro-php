<?php
use PHPUnit\Framework\TestCase;

/**
 * Tests for the DI Container
 */
class ContainerTest extends TestCase {

	protected function setUp(): void {
		Container::reset_instance();
	}

	protected function tearDown(): void {
		Container::reset_instance();
	}

	// --- Instance Management ---

	public function test_instance_returns_same_container(): void {
		$c1 = Container::instance();
		$c2 = Container::instance();
		$this->assertSame($c1, $c2);
	}

	public function test_reset_instance_creates_new_container(): void {
		$c1 = Container::instance();
		Container::reset_instance();
		$c2 = Container::instance();
		$this->assertNotSame($c1, $c2);
	}

	// --- Singleton Registration ---

	public function test_singleton_resolves_same_instance(): void {
		$c = Container::instance();
		$c->singleton('counter', function() {
			return new ContainerTestCounter();
		});

		$a = $c->get('counter');
		$a->increment();
		$b = $c->get('counter');

		$this->assertSame($a, $b);
		$this->assertEquals(1, $b->count);
	}

	public function test_singleton_lazy_creation(): void {
		$created = false;
		$c = Container::instance();
		$c->singleton('lazy', function() use (&$created) {
			$created = true;
			return 'value';
		});

		$this->assertFalse($created);
		$result = $c->get('lazy');
		$this->assertTrue($created);
		$this->assertEquals('value', $result);
	}

	// --- Factory Registration ---

	public function test_factory_creates_new_instance_each_time(): void {
		$c = Container::instance();
		$c->factory('counter', function() {
			return new ContainerTestCounter();
		});

		$a = $c->get('counter');
		$a->increment();
		$b = $c->get('counter');

		$this->assertNotSame($a, $b);
		$this->assertEquals(1, $a->count);
		$this->assertEquals(0, $b->count);
	}

	// --- Bind Instance ---

	public function test_bind_stores_instance_directly(): void {
		$c = Container::instance();
		$obj = new ContainerTestCounter();
		$obj->increment();

		$c->bind('counter', $obj);
		$result = $c->get('counter');

		$this->assertSame($obj, $result);
		$this->assertEquals(1, $result->count);
	}

	// --- Has / Get Service Names ---

	public function test_has_returns_true_for_registered(): void {
		$c = Container::instance();
		$c->singleton('test', function() { return 1; });

		$this->assertTrue($c->has('test'));
		$this->assertFalse($c->has('nonexistent'));
	}

	public function test_has_returns_true_for_bound_instances(): void {
		$c = Container::instance();
		$c->bind('test', 'value');

		$this->assertTrue($c->has('test'));
	}

	public function test_get_service_names(): void {
		$c = Container::instance();
		$c->singleton('a', function() { return 1; });
		$c->factory('b', function() { return 2; });
		$c->bind('c', 3);

		$names = $c->get_service_names();
		sort($names);
		$this->assertEquals(['a', 'b', 'c'], $names);
	}

	// --- Error Handling ---

	public function test_get_throws_for_unknown_service(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Service 'unknown' is not registered");

		Container::instance()->get('unknown');
	}

	// --- Container Injection ---

	public function test_factory_receives_container(): void {
		$c = Container::instance();
		$c->bind('db_host', 'localhost');
		$c->singleton('connection', function($container) {
			return 'connected to ' . $container->get('db_host');
		});

		$this->assertEquals('connected to localhost', $c->get('connection'));
	}

	// --- Static Shortcut ---

	public function test_get_service_static_shortcut(): void {
		$c = Container::instance();
		$c->bind('greeting', 'hello');

		$this->assertEquals('hello', Container::get_service('greeting'));
	}

	// --- Overwriting ---

	public function test_singleton_can_be_overwritten(): void {
		$c = Container::instance();
		$c->singleton('val', function() { return 'old'; });
		$c->get('val'); // Resolve and cache

		$c->singleton('val', function() { return 'new'; });
		$this->assertEquals('new', $c->get('val'));
	}

	public function test_bind_overwrites_singleton(): void {
		$c = Container::instance();
		$c->singleton('val', function() { return 'factory'; });
		$c->bind('val', 'direct');

		$this->assertEquals('direct', $c->get('val'));
	}

	// --- Reset ---

	public function test_reset_clears_all(): void {
		$c = Container::instance();
		$c->singleton('a', function() { return 1; });
		$c->bind('b', 2);

		$c->reset();

		$this->assertFalse($c->has('a'));
		$this->assertFalse($c->has('b'));
		$this->assertEmpty($c->get_service_names());
	}
}

/**
 * Test helper: Simple counter object
 */
class ContainerTestCounter {
	public $count = 0;
	public function increment(): void { $this->count++; }
}
