<?php
interface INewsSiteItem {
	/**
	 * Return publication date
	 * 
	 * @return int
	 */
	public function get_publication_date(); 

	/**
	 * Return publication keywords
	 * 
	 * @return array
	 */
	public function get_publication_keywords(); 
}
