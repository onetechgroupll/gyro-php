<?php
use PHPUnit\Framework\TestCase;

/**
 * Tests for Middleware system: MiddlewareStack, MiddlewareBase, MiddlewareRenderDecorator
 */
class MiddlewareTest extends TestCase {

	protected function setUp(): void {
		MiddlewareStack::clear();
	}

	protected function tearDown(): void {
		MiddlewareStack::clear();
	}

	// --- MiddlewareStack Tests ---

	public function test_add_and_get_middleware(): void {
		$mw = new TestMiddleware();
		MiddlewareStack::add($mw);

		$all = MiddlewareStack::get_all();
		$this->assertCount(1, $all);
		$this->assertSame($mw, $all[0]);
	}

	public function test_middleware_count(): void {
		$this->assertEquals(0, MiddlewareStack::count());
		MiddlewareStack::add(new TestMiddleware());
		$this->assertEquals(1, MiddlewareStack::count());
		MiddlewareStack::add(new TestMiddleware());
		$this->assertEquals(2, MiddlewareStack::count());
	}

	public function test_clear_removes_all(): void {
		MiddlewareStack::add(new TestMiddleware());
		MiddlewareStack::add(new TestMiddleware());
		MiddlewareStack::clear();
		$this->assertEquals(0, MiddlewareStack::count());
		$this->assertEmpty(MiddlewareStack::get_all());
	}

	public function test_priority_ordering(): void {
		$mw_late = new TestMiddleware('late');
		$mw_early = new TestMiddleware('early');
		$mw_default = new TestMiddleware('default');

		MiddlewareStack::add($mw_late, 200);
		MiddlewareStack::add($mw_early, 10);
		MiddlewareStack::add($mw_default, 100);

		$all = MiddlewareStack::get_all();
		$this->assertCount(3, $all);
		$this->assertSame($mw_early, $all[0]);
		$this->assertSame($mw_default, $all[1]);
		$this->assertSame($mw_late, $all[2]);
	}

	public function test_default_priority_is_100(): void {
		$mw1 = new TestMiddleware('first');
		$mw2 = new TestMiddleware('second');

		MiddlewareStack::add($mw1);
		MiddlewareStack::add($mw2);

		$all = MiddlewareStack::get_all();
		// Same priority, so order is preserved
		$this->assertSame($mw1, $all[0]);
		$this->assertSame($mw2, $all[1]);
	}

	// --- MiddlewareBase Tests ---

	public function test_base_handle_passes_through(): void {
		$base = new MiddlewareBase();
		// Should not throw, just pass through
		$base->handle(null, null);
		$this->assertTrue(true);
	}

	public function test_base_process_response_returns_content(): void {
		$base = new MiddlewareBase();
		$result = $base->process_response(null, '<html>test</html>');
		$this->assertEquals('<html>test</html>', $result);
	}

	// --- MiddlewareRenderDecorator Tests ---

	public function test_render_decorator_wraps_middleware(): void {
		$mw = new TestMiddleware('test');
		$decorator = new MiddlewareRenderDecorator($mw);

		$this->assertInstanceOf(IRenderDecorator::class, $decorator);
	}

	// --- Custom Middleware Implementation Test ---

	public function test_custom_middleware_handle(): void {
		$mw = new HeaderAddingMiddleware('X-Custom', 'TestValue');
		$mw->handle(null, null);
		$this->assertEquals('TestValue', $mw->getLastHeaderValue());
	}

	public function test_custom_middleware_process_response(): void {
		$mw = new ContentModifyingMiddleware('<!-- footer -->');
		$result = $mw->process_response(null, '<html>body</html>');
		$this->assertEquals('<html>body</html><!-- footer -->', $result);
	}
}

/**
 * Test helper: Simple middleware with label
 */
class TestMiddleware extends MiddlewareBase {
	public $label;
	public function __construct($label = '') {
		$this->label = $label;
	}
}

/**
 * Test helper: Middleware that adds a header
 */
class HeaderAddingMiddleware extends MiddlewareBase {
	private $header_name;
	private $header_value;
	private $last_value;

	public function __construct($name, $value) {
		$this->header_name = $name;
		$this->header_value = $value;
	}

	public function handle($page_data, $next) {
		$this->last_value = $this->header_value;
	}

	public function getLastHeaderValue() {
		return $this->last_value;
	}
}

/**
 * Test helper: Middleware that appends content
 */
class ContentModifyingMiddleware extends MiddlewareBase {
	private $append;

	public function __construct($append) {
		$this->append = $append;
	}

	public function process_response($page_data, $content) {
		return $content . $this->append;
	}
}
