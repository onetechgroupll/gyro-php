<?php
namespace Gyro\Core\Middleware;

use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Bridge that wraps a Gyro IMiddleware as a PSR-15 MiddlewareInterface
 *
 * Allows Gyro middleware to be used in external PSR-15 middleware stacks.
 *
 * Usage:
 *   $gyroMiddleware = new MyGyroMiddleware();
 *   $psr15Middleware = new GyroPsr15Bridge($gyroMiddleware);
 *   $externalStack->pipe($psr15Middleware);
 */
class GyroPsr15Bridge implements PsrMiddlewareInterface
{
	private \IMiddleware $gyroMiddleware;

	public function __construct(\IMiddleware $gyroMiddleware)
	{
		$this->gyroMiddleware = $gyroMiddleware;
	}

	/**
	 * Get the wrapped Gyro middleware
	 */
	public function getGyroMiddleware(): \IMiddleware
	{
		return $this->gyroMiddleware;
	}

	/**
	 * Process an incoming server request via the Gyro middleware
	 *
	 * The PSR-7 request is stored in a simple page data wrapper and passed
	 * to the Gyro middleware's handle() method. The response from the next
	 * handler is then passed through process_response().
	 *
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		// Create a minimal page data wrapper that holds the PSR-7 request
		$pageData = new Psr15PageData($request);

		// Create a next handler adapter
		$nextAdapter = new class($handler, $request) {
			private RequestHandlerInterface $handler;
			private ServerRequestInterface $request;

			public function __construct(RequestHandlerInterface $handler, ServerRequestInterface $request)
			{
				$this->handler = $handler;
				$this->request = $request;
			}

			public function initialize($pageData): void
			{
				// This method exists so the Gyro middleware can call $next->initialize()
			}

			public function getHandler(): RequestHandlerInterface
			{
				return $this->handler;
			}

			public function getRequest(): ServerRequestInterface
			{
				return $this->request;
			}
		};

		// Run the Gyro middleware
		$this->gyroMiddleware->handle($pageData, $nextAdapter);

		// Delegate to the next PSR-15 handler
		$response = $handler->handle($request);

		// Let Gyro middleware process the response content
		$body = (string)$response->getBody();
		$processed = $this->gyroMiddleware->process_response($pageData, $body);

		// If content was modified, create a new response with updated body
		if ($processed !== $body) {
			$newBody = new class($processed) implements \Psr\Http\Message\StreamInterface {
				private string $content;
				public function __construct(string $content) { $this->content = $content; }
				public function __toString(): string { return $this->content; }
				public function close(): void {}
				public function detach() { return null; }
				public function getSize(): ?int { return strlen($this->content); }
				public function tell(): int { return 0; }
				public function eof(): bool { return true; }
				public function isSeekable(): bool { return false; }
				public function seek(int $offset, int $whence = SEEK_SET): void {}
				public function rewind(): void {}
				public function isWritable(): bool { return false; }
				public function write(string $string): int { return 0; }
				public function isReadable(): bool { return true; }
				public function read(int $length): string { return $this->content; }
				public function getContents(): string { return $this->content; }
				public function getMetadata(?string $key = null) { return $key ? null : []; }
			};
			$response = $response->withBody($newBody);
		}

		return $response;
	}
}

/**
 * Minimal page data wrapper for the PSR-15 bridge
 *
 * Holds a PSR-7 request so Gyro middleware can access it.
 */
class Psr15PageData
{
	private ServerRequestInterface $request;

	/** @var array<string, mixed> */
	private array $attributes = [];

	/** @var int|null */
	public ?int $status_code = null;

	public function __construct(ServerRequestInterface $request)
	{
		$this->request = $request;
	}

	public function get_attribute(string $name, mixed $default = null): mixed
	{
		if ($name === 'psr7.request') {
			return $this->request;
		}
		return $this->attributes[$name] ?? $default;
	}

	public function set_attribute(string $name, mixed $value): void
	{
		$this->attributes[$name] = $value;
	}

	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}
}
