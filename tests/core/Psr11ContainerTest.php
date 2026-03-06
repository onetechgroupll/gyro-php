<?php
/**
 * Tests for PSR-11 Container compliance (Phase 16)
 */

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Gyro\Lib\Components\Exception\ServiceNotFoundException;
use PHPUnit\Framework\TestCase;

class Psr11ContainerTest extends TestCase
{
	protected function setUp(): void
	{
		Container::reset_instance();
	}

	protected function tearDown(): void
	{
		Container::reset_instance();
	}

	public function test_implements_psr11_interface(): void
	{
		$container = Container::instance();
		$this->assertInstanceOf(ContainerInterface::class, $container);
	}

	public function test_get_returns_registered_service(): void
	{
		$container = Container::instance();
		$container->singleton('test', function () {
			return 'hello';
		});
		$this->assertEquals('hello', $container->get('test'));
	}

	public function test_get_throws_not_found_exception_for_missing_service(): void
	{
		$container = Container::instance();
		$this->expectException(NotFoundExceptionInterface::class);
		$container->get('nonexistent');
	}

	public function test_not_found_exception_is_service_not_found(): void
	{
		$container = Container::instance();
		$this->expectException(ServiceNotFoundException::class);
		$container->get('missing_service');
	}

	public function test_not_found_exception_message(): void
	{
		$container = Container::instance();
		try {
			$container->get('my_service');
			$this->fail('Expected ServiceNotFoundException');
		} catch (ServiceNotFoundException $e) {
			$this->assertStringContainsString('my_service', $e->getMessage());
		}
	}

	public function test_has_returns_true_for_registered(): void
	{
		$container = Container::instance();
		$container->singleton('exists', function () { return 1; });
		$this->assertTrue($container->has('exists'));
	}

	public function test_has_returns_false_for_unregistered(): void
	{
		$container = Container::instance();
		$this->assertFalse($container->has('does_not_exist'));
	}

	public function test_has_returns_true_for_bound_instance(): void
	{
		$container = Container::instance();
		$container->bind('obj', new \stdClass());
		$this->assertTrue($container->has('obj'));
	}

	public function test_psr11_get_with_string_id(): void
	{
		$container = Container::instance();
		$container->factory('factory_test', function () {
			return new \stdClass();
		});
		$result = $container->get('factory_test');
		$this->assertInstanceOf(\stdClass::class, $result);
	}

	public function test_singleton_returns_same_instance(): void
	{
		$container = Container::instance();
		$container->singleton('shared', function () {
			return new \stdClass();
		});
		$a = $container->get('shared');
		$b = $container->get('shared');
		$this->assertSame($a, $b);
	}

	public function test_factory_returns_different_instances(): void
	{
		$container = Container::instance();
		$container->factory('new_each_time', function () {
			return new \stdClass();
		});
		$a = $container->get('new_each_time');
		$b = $container->get('new_each_time');
		$this->assertNotSame($a, $b);
	}

	public function test_container_receives_self_in_factory(): void
	{
		$container = Container::instance();
		$container->bind('config_value', 'hello_world');
		$container->singleton('service', function (ContainerInterface $c) {
			return $c->get('config_value');
		});
		$this->assertEquals('hello_world', $container->get('service'));
	}
}
