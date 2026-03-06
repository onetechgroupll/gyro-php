<?php
/**
 * Tests for PSR-15 Middleware adapters (Phase 16)
 */

use Gyro\Core\Middleware\Psr15Adapter;
use Gyro\Core\Middleware\GyroPsr15Bridge;
use Gyro\Core\Middleware\Psr15PageData;
use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;

class Psr15MiddlewareTest extends TestCase
{
	public function test_psr15_adapter_implements_imiddleware(): void
	{
		$psrMw = $this->createMock(PsrMiddlewareInterface::class);
		$adapter = new Psr15Adapter($psrMw);
		$this->assertInstanceOf(\IMiddleware::class, $adapter);
	}

	public function test_psr15_adapter_get_psr_middleware(): void
	{
		$psrMw = $this->createMock(PsrMiddlewareInterface::class);
		$adapter = new Psr15Adapter($psrMw);
		$this->assertSame($psrMw, $adapter->getPsrMiddleware());
	}

	public function test_psr15_adapter_passthrough_without_psr7_request(): void
	{
		$psrMw = $this->createMock(PsrMiddlewareInterface::class);
		// process() should NOT be called when there's no PSR-7 request
		$psrMw->expects($this->never())->method('process');

		$adapter = new Psr15Adapter($psrMw);
		// Use a simple object without get_attribute method
		$pageData = new \stdClass();
		$next = new \stdClass();

		$adapter->handle($pageData, $next);
		// No exception = pass
	}

	public function test_psr15_adapter_process_response_returns_content(): void
	{
		$psrMw = $this->createMock(PsrMiddlewareInterface::class);
		$adapter = new Psr15Adapter($psrMw);

		$result = $adapter->process_response(null, '<html>content</html>');
		$this->assertEquals('<html>content</html>', $result);
	}

	public function test_gyro_psr15_bridge_implements_psr15(): void
	{
		$gyroMw = $this->createMock(\IMiddleware::class);
		$bridge = new GyroPsr15Bridge($gyroMw);
		$this->assertInstanceOf(PsrMiddlewareInterface::class, $bridge);
	}

	public function test_gyro_psr15_bridge_get_gyro_middleware(): void
	{
		$gyroMw = $this->createMock(\IMiddleware::class);
		$bridge = new GyroPsr15Bridge($gyroMw);
		$this->assertSame($gyroMw, $bridge->getGyroMiddleware());
	}

	public function test_gyro_psr15_bridge_calls_handle_and_process_response(): void
	{
		$gyroMw = $this->createMock(\IMiddleware::class);
		$gyroMw->expects($this->once())->method('handle');
		$gyroMw->expects($this->once())->method('process_response')
			->willReturnArgument(1); // Return content unchanged

		$bridge = new GyroPsr15Bridge($gyroMw);

		$request = $this->createMock(ServerRequestInterface::class);

		$body = $this->createMock(\Psr\Http\Message\StreamInterface::class);
		$body->method('__toString')->willReturn('response body');

		$response = $this->createMock(ResponseInterface::class);
		$response->method('getBody')->willReturn($body);

		$handler = $this->createMock(RequestHandlerInterface::class);
		$handler->method('handle')->willReturn($response);

		$result = $bridge->process($request, $handler);
		$this->assertSame($response, $result);
	}

	public function test_gyro_psr15_bridge_modifies_content(): void
	{
		$gyroMw = $this->createMock(\IMiddleware::class);
		$gyroMw->method('handle');
		$gyroMw->method('process_response')
			->willReturn('MODIFIED CONTENT');

		$bridge = new GyroPsr15Bridge($gyroMw);

		$request = $this->createMock(ServerRequestInterface::class);

		$body = $this->createMock(\Psr\Http\Message\StreamInterface::class);
		$body->method('__toString')->willReturn('original');

		$newResponse = $this->createMock(ResponseInterface::class);

		$response = $this->createMock(ResponseInterface::class);
		$response->method('getBody')->willReturn($body);
		$response->method('withBody')->willReturn($newResponse);

		$handler = $this->createMock(RequestHandlerInterface::class);
		$handler->method('handle')->willReturn($response);

		$result = $bridge->process($request, $handler);
		$this->assertSame($newResponse, $result);
	}

	public function test_psr15_page_data_holds_request(): void
	{
		$request = $this->createMock(ServerRequestInterface::class);
		$pageData = new Psr15PageData($request);

		$this->assertSame($request, $pageData->getRequest());
		$this->assertSame($request, $pageData->get_attribute('psr7.request'));
	}

	public function test_psr15_page_data_attributes(): void
	{
		$request = $this->createMock(ServerRequestInterface::class);
		$pageData = new Psr15PageData($request);

		$this->assertNull($pageData->get_attribute('nonexistent'));
		$this->assertEquals('default', $pageData->get_attribute('nonexistent', 'default'));

		$pageData->set_attribute('custom', 'value');
		$this->assertEquals('value', $pageData->get_attribute('custom'));
	}

	public function test_psr15_page_data_status_code(): void
	{
		$request = $this->createMock(ServerRequestInterface::class);
		$pageData = new Psr15PageData($request);

		$this->assertNull($pageData->status_code);
		$pageData->status_code = 404;
		$this->assertEquals(404, $pageData->status_code);
	}
}
