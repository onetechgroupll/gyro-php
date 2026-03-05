<?php
/**
 * A generic list
 * 
 * @author Gerd Riesselmann
 * @ingroup View
 */
class WidgetList implements IWidget {
	protected $page_data;
	protected $parent_view;
	protected $items;
	protected $empty_message;
	
	public static function output(PageData $page_data, IView $parent_view, $items, $empty_message = '', $policy = 0) {
		$widget = new WidgetList($page_data, $parent_view, $items, $empty_message);
		return $widget->render($policy);			
	} 

	public function __construct(PageData $page_data, IView $parent_view, $items, $empty_message = '') {
		$this->page_data = $page_data;
		$this->parent_view = $parent_view;
		$this->items = $items;
		$this->empty_message = $empty_message;	
	} 
	
	public function render($policy = 0) {
		$ret = '';
		$items = Arr::force($this->items, false);
		$view = ViewFactory::create_view(IViewFactory::MESSAGE, 'widgets/list');
		$view->assign('page_data', $this->page_data);
		$view->assign('parent_view', $this->parent_view);
		$view->assign('items', $this->render_items($this->page_data, $items, $policy));
		$view->assign('policy', $policy);
		$view->assign('empty_message', $this->empty_message);
		$ret = $view->render();
		return $ret;
	}
	
	protected function render_items($page_data, $items, $policy) {
		$ret = array();
		$i = 1;
		$c = count($items);
		foreach($items as $item) {
			$cls = array('listitem');
			if ($item instanceof IDataObject) {
				$cls[] = 'listitem-' . $item->get_table_name();
			}
			if ($i == 1) { $cls[] = 'first'; }
			if ($i == $c) { $cls[] = 'last'; }
			$ret[] = html::div(WidgetListItem::output($item, $policy), implode(' ', $cls));
			$i++;
		}		
		return $ret;
	}
}