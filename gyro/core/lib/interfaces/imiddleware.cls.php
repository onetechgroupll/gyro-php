<?php
/**
 * Interface for middleware components in the request/response pipeline
 *
 * Middleware can intercept the request before it reaches the controller
 * and modify the response after the controller has processed it.
 *
 * @author Gyro-PHP Framework
 * @ingroup Interfaces
 */
interface IMiddleware {
	/**
	 * Process the request/response
	 *
	 * Called during the initialize phase (before controller action).
	 * Set $page_data->status_code to short-circuit the pipeline.
	 *
	 * @param PageData $page_data The current page data
	 * @param IRenderDecorator $next The next handler in the chain
	 * @return void
	 */
	public function handle($page_data, $next);

	/**
	 * Process the response after rendering
	 *
	 * Called during the render_page phase (after controller action).
	 * Can modify headers, content, etc.
	 *
	 * @param PageData $page_data The current page data
	 * @param string $content The rendered content
	 * @return string The (possibly modified) content
	 */
	public function process_response($page_data, $content);
}
