<?php
/**
 * Tests for PSR-16 Cache adapter (Phase 16)
 */

use Gyro\Lib\Cache\Psr16Adapter;
use Gyro\Lib\Cache\InvalidCacheKeyException;
use Psr\SimpleCache\CacheInterface;
use PHPUnit\Framework\TestCase;

/**
 * Simple in-memory ICachePersister mock for testing PSR-16 adapter
 */
class MockCachePersister implements ICachePersister
{
	/** @var array<string, array{content: string, ttl: int, time: int}> */
	public array $store = [];

	public function is_cached(mixed $cache_keys): bool
	{
		return isset($this->store[$cache_keys]);
	}

	public function read(mixed $cache_keys): ICacheItem|false
	{
		if (!isset($this->store[$cache_keys])) {
			return false;
		}
		$data = $this->store[$cache_keys];
		return new MockCacheItem($data['content'], $data['time'], $data['ttl']);
	}

	public function store(mixed $cache_keys, string $content, int $cache_life_time, mixed $data = '', bool $is_compressed = false): void
	{
		$this->store[$cache_keys] = [
			'content' => $content,
			'ttl' => $cache_life_time,
			'time' => time(),
		];
	}

	public function clear(mixed $cache_keys = NULL): void
	{
		if ($cache_keys === null) {
			$this->store = [];
		} else {
			unset($this->store[$cache_keys]);
		}
	}

	public function remove_expired(): void
	{
		// no-op for tests
	}
}

class MockCacheItem implements ICacheItem
{
	private string $content;
	private int $created;
	private int $ttl;

	public function __construct(string $content, int $created, int $ttl)
	{
		$this->content = $content;
		$this->created = $created;
		$this->ttl = $ttl;
	}

	public function get_creationdate(): mixed { return $this->created; }
	public function get_expirationdate(): mixed { return $this->created + $this->ttl; }
	public function get_data(): mixed { return ''; }
	public function get_content_plain(): string { return $this->content; }
	public function get_content_compressed(): string { return gzencode($this->content); }
}

class Psr16CacheTest extends TestCase
{
	private MockCachePersister $persister;
	private Psr16Adapter $cache;

	protected function setUp(): void
	{
		$this->persister = new MockCachePersister();
		$this->cache = new Psr16Adapter($this->persister);
	}

	public function test_implements_psr16_interface(): void
	{
		$this->assertInstanceOf(CacheInterface::class, $this->cache);
	}

	public function test_get_returns_default_for_missing_key(): void
	{
		$this->assertNull($this->cache->get('nonexistent'));
		$this->assertEquals('default', $this->cache->get('nonexistent', 'default'));
	}

	public function test_set_and_get(): void
	{
		$this->assertTrue($this->cache->set('key', 'value'));
		$this->assertEquals('value', $this->cache->get('key'));
	}

	public function test_set_with_integer_value(): void
	{
		$this->cache->set('num', 42);
		$this->assertSame(42, $this->cache->get('num'));
	}

	public function test_set_with_array_value(): void
	{
		$data = ['a' => 1, 'b' => [2, 3]];
		$this->cache->set('arr', $data);
		$this->assertEquals($data, $this->cache->get('arr'));
	}

	public function test_set_with_null_value(): void
	{
		$this->cache->set('null_val', null);
		// null should be stored and retrievable (distinct from "not found")
		$this->assertTrue($this->cache->has('null_val'));
	}

	public function test_set_with_ttl(): void
	{
		$this->cache->set('ttl_test', 'value', 600);
		$this->assertEquals(600, $this->persister->store['ttl_test']['ttl']);
	}

	public function test_set_with_date_interval_ttl(): void
	{
		$interval = new \DateInterval('PT1H'); // 1 hour
		$this->cache->set('interval_test', 'value', $interval);
		$this->assertEquals(3600, $this->persister->store['interval_test']['ttl']);
	}

	public function test_delete(): void
	{
		$this->cache->set('to_delete', 'value');
		$this->assertTrue($this->cache->has('to_delete'));
		$this->assertTrue($this->cache->delete('to_delete'));
		$this->assertFalse($this->cache->has('to_delete'));
	}

	public function test_clear(): void
	{
		$this->cache->set('a', 1);
		$this->cache->set('b', 2);
		$this->assertTrue($this->cache->clear());
		$this->assertFalse($this->cache->has('a'));
		$this->assertFalse($this->cache->has('b'));
	}

	public function test_has(): void
	{
		$this->assertFalse($this->cache->has('key'));
		$this->cache->set('key', 'value');
		$this->assertTrue($this->cache->has('key'));
	}

	public function test_get_multiple(): void
	{
		$this->cache->set('a', 1);
		$this->cache->set('b', 2);
		$result = $this->cache->getMultiple(['a', 'b', 'c'], 'default');
		$this->assertEquals(['a' => 1, 'b' => 2, 'c' => 'default'], $result);
	}

	public function test_set_multiple(): void
	{
		$this->assertTrue($this->cache->setMultiple(['x' => 10, 'y' => 20]));
		$this->assertEquals(10, $this->cache->get('x'));
		$this->assertEquals(20, $this->cache->get('y'));
	}

	public function test_delete_multiple(): void
	{
		$this->cache->set('a', 1);
		$this->cache->set('b', 2);
		$this->cache->set('c', 3);
		$this->assertTrue($this->cache->deleteMultiple(['a', 'c']));
		$this->assertFalse($this->cache->has('a'));
		$this->assertTrue($this->cache->has('b'));
		$this->assertFalse($this->cache->has('c'));
	}

	public function test_empty_key_throws_exception(): void
	{
		$this->expectException(\Psr\SimpleCache\InvalidArgumentException::class);
		$this->cache->get('');
	}

	public function test_reserved_characters_throw_exception(): void
	{
		$reservedKeys = ['key{', 'key}', 'key(', 'key)', 'key/', 'key\\', 'key@', 'key:'];
		$exceptionCount = 0;
		foreach ($reservedKeys as $key) {
			try {
				$this->cache->get($key);
			} catch (InvalidCacheKeyException $e) {
				$exceptionCount++;
			}
		}
		$this->assertEquals(count($reservedKeys), $exceptionCount);
	}

	public function test_set_with_zero_ttl_deletes(): void
	{
		$this->cache->set('ephemeral', 'value', 3600);
		$this->assertTrue($this->cache->has('ephemeral'));
		$this->cache->set('ephemeral', 'value', 0);
		$this->assertFalse($this->cache->has('ephemeral'));
	}

	public function test_set_and_get_boolean_false(): void
	{
		$this->cache->set('bool_false', false);
		$this->assertFalse($this->cache->get('bool_false', 'NOT_FOUND'));
	}
}
