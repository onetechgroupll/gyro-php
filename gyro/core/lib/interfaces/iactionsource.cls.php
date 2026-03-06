<?php
/**
 * Interface for all DAO objects having actions
 * 
 * @author Gerd Riesselmann
 * @ingroup Interfaces
 */ 
interface IActionSource {
	/**
	 * Get all actions
	 *
	 * @param mixed $user The current user or false if no user is logged on
	 * @param string $context The context. Some actions may not be appropriate in some situations. For example,
	 *               action 'edit' should not be returned when editing. This can be expressed through a
	 *               context named 'edit'. Default context is 'view'.
	 * @param mixed $params
	 * @return Array Associative array with action url as key and action description as value 
	 */
	public function get_actions($user, $context = 'view', $params = false);
	
	/**
	 * Identify for generic actionh processing
	 * 
	 * @return string
	 */
	public function get_action_source_name();
}
