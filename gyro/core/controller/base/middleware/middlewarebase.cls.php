<?php
/**
 * Base class for middleware implementations
 *
 * Provides default pass-through behavior. Extend this class and override
 * handle() and/or process_response() to add custom middleware logic.
 *
 * @author Gyro-PHP Framework
 * @ingroup Controller
 */
class MiddlewareBase implements IMiddleware {
	/**
	 * Process the request (before controller action)
	 *
	 * Default implementation passes through to the next handler.
	 * Override to add pre-processing logic.
	 *
	 * @param PageData $page_data
	 * @param IRenderDecorator $next
	 * @return void
	 */
	public function handle($page_data, $next) {
		// Default: pass through
	}

	/**
	 * Process the response (after controller action)
	 *
	 * Default implementation returns content unchanged.
	 * Override to modify response content or headers.
	 *
	 * @param PageData $page_data
	 * @param string $content
	 * @return string
	 */
	public function process_response($page_data, $content) {
		return $content;
	}
}
