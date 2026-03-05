<?php
/**
 * Interface for cache header managers
 * 
 * Cache header managers are resposible for sending HTTP cache headers
 */
interface ICacheHeaderManager {
	/**
	 * Send cache headers
	 * 
	 * @param string $content
	 * @param int $expirationdate Unix timestamp
	 * @param int $lastmodifieddate Unix timestamp
	 */
	public function send_headers(&$content, $expirationdate, $lastmodifieddate);
}