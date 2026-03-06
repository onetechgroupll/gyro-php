<?php
/**
 * Tests that PSR-4 namespace aliases work correctly for all migrated classes.
 *
 * Verifies that both the global class name (e.g. Config) and the namespaced
 * class name (e.g. Gyro\Core\Config) refer to the same class.
 */

use Gyro\Core\Config as NamespacedConfig;
use Gyro\Core\Common as NamespacedCommon;
use Gyro\Core\DB as NamespacedDB;
use Gyro\Lib\Components\Logger as NamespacedLogger;
use Gyro\Lib\Components\Container as NamespacedContainer;
use Gyro\Lib\Helpers\Env as NamespacedEnv;
use Gyro\Lib\Helpers\GyroString as NamespacedGyroString;
use Gyro\Lib\Helpers\Arr as NamespacedArr;
use Gyro\Lib\Helpers\Cast as NamespacedCast;
use Gyro\Lib\Helpers\Url as NamespacedUrl;

use PHPUnit\Framework\TestCase;

class NamespaceAliasTest extends TestCase
{
	/**
	 * @dataProvider classAliasProvider
	 */
	public function test_namespace_alias_exists(string $globalName, string $namespacedName): void
	{
		$this->assertTrue(
			class_exists($namespacedName),
			"Namespaced class $namespacedName should exist"
		);
		$this->assertTrue(
			class_exists($globalName),
			"Global class $globalName should exist"
		);
	}

	/**
	 * @dataProvider classAliasProvider
	 */
	public function test_namespace_alias_is_same_class(string $globalName, string $namespacedName): void
	{
		// Both names should resolve to the same class
		$this->assertEquals(
			$globalName,
			(new \ReflectionClass($namespacedName))->getName(),
			"$namespacedName should be an alias for $globalName"
		);
	}

	public static function classAliasProvider(): array
	{
		return [
			'Config'    => ['Config', 'Gyro\\Core\\Config'],
			'Common'    => ['Common', 'Gyro\\Core\\Common'],
			'DB'        => ['DB', 'Gyro\\Core\\DB'],
			'Logger'    => ['Logger', 'Gyro\\Lib\\Components\\Logger'],
			'Container' => ['Container', 'Gyro\\Lib\\Components\\Container'],
			'Env'       => ['Env', 'Gyro\\Lib\\Helpers\\Env'],
			'GyroString'=> ['GyroString', 'Gyro\\Lib\\Helpers\\GyroString'],
			'Arr'       => ['Arr', 'Gyro\\Lib\\Helpers\\Arr'],
			'Cast'      => ['Cast', 'Gyro\\Lib\\Helpers\\Cast'],
			'Url'       => ['Url', 'Gyro\\Lib\\Helpers\\Url'],
		];
	}

	public function test_namespaced_config_has_feature(): void
	{
		NamespacedConfig::set_feature('TEST_NS', true);
		$this->assertTrue(NamespacedConfig::has_feature('TEST_NS'));
		$this->assertTrue(\Config::has_feature('TEST_NS'));
		NamespacedConfig::set_feature('TEST_NS', false);
	}

	public function test_namespaced_env_get(): void
	{
		NamespacedEnv::reset();
		$this->assertNull(NamespacedEnv::get('NON_EXISTENT'));
		$this->assertEquals('default', NamespacedEnv::get('NON_EXISTENT', 'default'));
	}

	public function test_namespaced_arr_get_item(): void
	{
		$arr = ['key' => 'value'];
		$this->assertEquals('value', NamespacedArr::get_item($arr, 'key'));
		$this->assertEquals('default', NamespacedArr::get_item($arr, 'missing', 'default'));
	}

	public function test_namespaced_cast_int(): void
	{
		$this->assertEquals(42, NamespacedCast::int('42'));
		$this->assertEquals(0, NamespacedCast::int('abc'));
	}

	public function test_namespaced_gyrostring_escape(): void
	{
		$this->assertEquals('&lt;b&gt;test&lt;/b&gt;', NamespacedGyroString::escape('<b>test</b>'));
	}

	public function test_namespaced_container_singleton(): void
	{
		NamespacedContainer::reset_instance();
		$container = NamespacedContainer::instance();
		$container->singleton('test_ns', function () {
			return 'namespaced_value';
		});
		$this->assertEquals('namespaced_value', $container->get('test_ns'));
		$this->assertTrue($container->has('test_ns'));

		// Global Container should see the same instance
		$this->assertEquals('namespaced_value', \Container::get_service('test_ns'));

		NamespacedContainer::reset_instance();
	}

	public function test_namespaced_url_create(): void
	{
		$url = NamespacedUrl::create('http://example.com/path?key=value');
		$this->assertInstanceOf(NamespacedUrl::class, $url);
		$this->assertInstanceOf(\Url::class, $url);
		$this->assertEquals('example.com', $url->get_host());
		$this->assertEquals('path', $url->get_path());
	}

	public function test_use_statement_works(): void
	{
		// Verify that "use" imports resolve correctly via class_alias
		$this->assertTrue(is_a('Config', NamespacedConfig::class, true));
		$this->assertTrue(is_a('Gyro\\Core\\Config', 'Config', true));
	}

	public function test_instanceof_works_both_ways(): void
	{
		$url = new \Url('http://example.com');
		$this->assertInstanceOf(\Url::class, $url);
		$this->assertInstanceOf(NamespacedUrl::class, $url);
		$this->assertInstanceOf('Gyro\\Lib\\Helpers\\Url', $url);
	}
}
