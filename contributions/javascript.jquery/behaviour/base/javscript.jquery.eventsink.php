<?php
/**
 * EventSink to deal with JCSSManager events
 *
 * @author Gerd Riesselmann
 * @ingroup JQuery
 */
class JavascriptJQueryEventSink implements IEventSink {
	/**
	 * Invoked to handle events
	 * 
	 * Events can be anything, and they are invoked through the router
	 * One event is "cron", it has no parameters
	 * 
	 * @param string $event_name Event name
	 * @param mixed $event_params Event parameter(s)
	 */
	public function on_event($event_name, $event_params, &$result) {
		switch ($event_name) {
			case 'jcssmanager_compress':
				if ($event_params == JCSSManager::TYPE_JS && Config::get_value(ConfigJQuery::CDN) == '') {
					array_unshift($result, 'js/jquery.js');
				}
				break;
			case 'jcssmanager_collect':
				if ($event_params == JCSSManager::TYPE_JS && Config::has_feature(ConfigJQuery::ON_EVERY_PAGE)) {
					array_unshift($result, JQuery::get_head_data_file());
				}
				break;
		}
	}
}
