<?php
/**
 * Output twitter messages of airline
 */
class WidgetTweets implements IWidget {
	public $user;
	
	public static function output($user, $policy = self::NONE) {
		$w = new WidgetTweets($user);
		return $w->render($policy);
	}
	
	public function __construct($user) {
		$this->user = $user;
	}
	
	public function render($policy = 0) {
		Load::models('tweets');
		$tweets = Tweets::get_latest_for_user($this->user, 10);
		$page_data = false;
		$view = ViewFactory::create_view(IViewFactory::MESSAGE, 'widgets/tweets', $page_data);
		$view->assign('tweets', $tweets);
		return $view->render(); 
	}
}