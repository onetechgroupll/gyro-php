<?php
namespace Gyro\Core\Middleware;

use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Adapter that wraps a PSR-15 MiddlewareInterface as a Gyro IMiddleware
 *
 * Allows external PSR-15 middleware packages to be used in Gyro's middleware stack.
 *
 * Usage:
 *   $psrMiddleware = new SomeExternalMiddleware();
 *   $gyroMiddleware = new Psr15Adapter($psrMiddleware);
 *   MiddlewareStack::add($gyroMiddleware);
 */
class Psr15Adapter implements \IMiddleware
{
	private PsrMiddlewareInterface $psrMiddleware;

	/**
	 * Last PSR-7 response produced by the wrapped middleware
	 *
	 * @var ResponseInterface|null
	 */
	private ?ResponseInterface $lastResponse = null;

	public function __construct(PsrMiddlewareInterface $psrMiddleware)
	{
		$this->psrMiddleware = $psrMiddleware;
	}

	/**
	 * Get the wrapped PSR-15 middleware
	 */
	public function getPsrMiddleware(): PsrMiddlewareInterface
	{
		return $this->psrMiddleware;
	}

	/**
	 * Get the last PSR-7 response (if any) produced during handle()
	 */
	public function getLastResponse(): ?ResponseInterface
	{
		return $this->lastResponse;
	}

	/**
	 * Process the request through the PSR-15 middleware
	 *
	 * If a PSR-7 ServerRequestInterface is available in $page_data (via attribute 'psr7.request'),
	 * it is passed to the PSR-15 middleware. Otherwise this is a pass-through.
	 *
	 * @param mixed $page_data The Gyro PageData object
	 * @param mixed $next The next handler in the Gyro chain
	 */
	public function handle($page_data, $next)
	{
		$request = null;
		if (is_object($page_data) && method_exists($page_data, 'get_attribute')) {
			$request = $page_data->get_attribute('psr7.request');
		}

		if ($request instanceof ServerRequestInterface) {
			$handler = new class($next, $page_data) implements RequestHandlerInterface {
				private $next;
				private $pageData;
				private ?ResponseInterface $response = null;

				public function __construct($next, $pageData)
				{
					$this->next = $next;
					$this->pageData = $pageData;
				}

				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					// Delegate to Gyro's next handler
					if (is_object($this->next) && method_exists($this->next, 'initialize')) {
						$this->next->initialize($this->pageData);
					}
					// Return a minimal response if no PSR-7 response was produced
					if ($this->response === null) {
						throw new \RuntimeException('No PSR-7 response available from Gyro handler chain');
					}
					return $this->response;
				}

				public function setResponse(ResponseInterface $response): void
				{
					$this->response = $response;
				}
			};

			$this->lastResponse = $this->psrMiddleware->process($request, $handler);
		}
		// If no PSR-7 request available, just pass through to next Gyro handler
	}

	/**
	 * Pass-through for response processing
	 *
	 * @param mixed $page_data
	 * @param string $content
	 * @return string
	 */
	public function process_response($page_data, $content)
	{
		return $content;
	}
}
