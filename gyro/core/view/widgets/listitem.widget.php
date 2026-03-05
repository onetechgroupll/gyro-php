<?php
/**
 * A generic list's item
 * 
 * @author Gerd Riesselmann
 * @ingroup View
 */
class WidgetListItem implements IWidget {
	protected $page_data;
	protected $item;
	
	public static function output($item, $policy = 0) {
		$widget = new WidgetListItem($item);
		return $widget->render($policy);			
	} 

	public function __construct($item) {
		$this->item = $item;
	} 
	
	public function render($policy = 0) {
		$ret = '';
		if ($this->item instanceof IDataObject) {
			$model = $this->item->get_table_name();
			$view = $this->create_item_view($model);
			$view->assign('item', $this->item);
			$view->assign('policy', $policy);
			$ret = $view->render();
		}
		return $ret;
	}
	
	protected function create_item_view($model) {
		$paths = array(
			$model . '/inc/listitem',
			'widgets/listitem'
		); 		
		return ViewFactory::create_view(IViewFactory::MESSAGE, $paths);
	}
}