<?php
function javascript_jqueryui_check_preconditions() {
	$ret = new Status();
	Load::components('systemupdateinstaller');
	$root = Load::get_module_dir('javascript.jqueryui') . 'data/' . Config::get_value(ConfigJQueryUI::VERSION) . '/';
	/** @phpstan-ignore class.notFound, class.notFound */
	$ret->merge(SystemUpdateInstaller::copy_to_webroot($root, array('js'), SystemUpdateInstaller::COPY_OVERWRITE));
	/** @phpstan-ignore class.notFound, class.notFound */
	$ret->merge(SystemUpdateInstaller::copy_to_webroot($root, array('css'), SystemUpdateInstaller::COPY_NO_REPLACE));
	return $ret;
}
