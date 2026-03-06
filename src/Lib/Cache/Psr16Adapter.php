<?php
namespace Gyro\Lib\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 SimpleCache adapter for Gyro's ICachePersister
 *
 * Bridges between PSR-16 CacheInterface and Gyro's cache system,
 * allowing external packages that depend on PSR-16 to use Gyro's cache.
 *
 * Usage:
 *   $persister = new CacheFileImpl('/tmp/cache');
 *   $cache = new Psr16Adapter($persister);
 *   $cache->set('key', 'value', 3600);
 *   $value = $cache->get('key');
 */
class Psr16Adapter implements CacheInterface
{
	private \ICachePersister $persister;
	private int $defaultTtl;

	/**
	 * @param \ICachePersister $persister The Gyro cache backend
	 * @param int $defaultTtl Default TTL in seconds (0 = 1 hour)
	 */
	public function __construct(\ICachePersister $persister, int $defaultTtl = 3600)
	{
		$this->persister = $persister;
		$this->defaultTtl = $defaultTtl;
	}

	public function get(string $key, mixed $default = null): mixed
	{
		$this->validateKey($key);

		$item = $this->persister->read($key);
		if ($item === false) {
			return $default;
		}

		$content = $item->get_content_plain();
		$data = @unserialize($content);
		if ($data === false && $content !== serialize(false)) {
			return $default;
		}

		return $data;
	}

	public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
	{
		$this->validateKey($key);

		$seconds = $this->resolveTtl($ttl);
		if ($seconds !== null && $seconds <= 0) {
			return $this->delete($key);
		}

		$content = serialize($value);
		$this->persister->store($key, $content, $seconds ?? $this->defaultTtl);
		return true;
	}

	public function delete(string $key): bool
	{
		$this->validateKey($key);
		$this->persister->clear($key);
		return true;
	}

	public function clear(): bool
	{
		$this->persister->clear(null);
		return true;
	}

	public function getMultiple(iterable $keys, mixed $default = null): iterable
	{
		$result = [];
		foreach ($keys as $key) {
			$result[$key] = $this->get($key, $default);
		}
		return $result;
	}

	public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
	{
		$success = true;
		foreach ($values as $key => $value) {
			if (!$this->set($key, $value, $ttl)) {
				$success = false;
			}
		}
		return $success;
	}

	public function deleteMultiple(iterable $keys): bool
	{
		$success = true;
		foreach ($keys as $key) {
			if (!$this->delete($key)) {
				$success = false;
			}
		}
		return $success;
	}

	public function has(string $key): bool
	{
		$this->validateKey($key);
		return $this->persister->is_cached($key);
	}

	/**
	 * Resolve a TTL value to seconds
	 *
	 * @param null|int|\DateInterval $ttl
	 * @return int|null Seconds, or null for default
	 */
	private function resolveTtl(null|int|\DateInterval $ttl): ?int
	{
		if ($ttl === null) {
			return null;
		}
		if ($ttl instanceof \DateInterval) {
			return (int)(\DateTime::createFromFormat('U', '0')
				->add($ttl)
				->format('U'));
		}
		return $ttl;
	}

	/**
	 * Validate cache key per PSR-16 spec
	 *
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	private function validateKey(string $key): void
	{
		if ($key === '') {
			throw new InvalidCacheKeyException('Cache key must not be empty.');
		}
		if (preg_match('/[{}()\/@:\\\\]/', $key)) {
			throw new InvalidCacheKeyException(
				"Cache key '$key' contains reserved characters: {}()/\\@:"
			);
		}
	}
}

/**
 * PSR-16 InvalidArgumentException for cache key validation
 */
class InvalidCacheKeyException extends \InvalidArgumentException implements \Psr\SimpleCache\InvalidArgumentException
{
}
