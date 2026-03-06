<?php
/**
 * Adapter for pager to extract and set page from URL
 * 
 * @author Gerd Riesselmann
 * @ingroup Interfaces
 */
interface IPagerAdapter {
	/**
	 * Return current page
	 * 
	 * @return int
	 */
	public function get_current_page();
	
	/**
	 * Compute url for page
	 * 
	 * @param int $page
	 * @return Url
	 */
	public function get_url_for_page($page);
}