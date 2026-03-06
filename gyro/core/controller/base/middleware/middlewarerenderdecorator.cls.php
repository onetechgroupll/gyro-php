<?php
/**
 * RenderDecorator that wraps an IMiddleware instance
 *
 * Bridges the middleware pattern into the existing RenderDecorator chain.
 * This allows middleware to be used alongside traditional RenderDecorators.
 *
 * @author Gyro-PHP Framework
 * @ingroup Controller
 */
class MiddlewareRenderDecorator extends RenderDecoratorBase {
	/**
	 * The middleware instance
	 *
	 * @var IMiddleware
	 */
	private $middleware;

	/**
	 * Constructor
	 *
	 * @param IMiddleware $middleware The middleware to wrap
	 */
	public function __construct($middleware) {
		$this->middleware = $middleware;
	}

	/**
	 * Initialize phase — delegates to middleware handle()
	 *
	 * @param PageData $page_data
	 * @return void
	 */
	public function initialize($page_data) {
		$next = $this->get_next();
		$this->middleware->handle($page_data, $next);

		// Only continue chain if middleware didn't set an error status
		if (empty($page_data->status_code) || $page_data->status_code == CONTROLLER_OK) {
			$this->initialize_next($page_data);
		}
	}

	/**
	 * Render page phase — delegates to middleware process_response()
	 *
	 * @param PageData $page_data
	 * @param IRenderDecorator $content_render_decorator
	 * @param int|false $policy
	 * @return string|void
	 */
	public function render_page($page_data, $content_render_decorator, $policy = IView::NONE) {
		$content = $this->render_page_next($page_data, $content_render_decorator, $policy);
		if ($content !== null) {
			$content = $this->middleware->process_response($page_data, $content);
		}
		return $content;
	}
}
