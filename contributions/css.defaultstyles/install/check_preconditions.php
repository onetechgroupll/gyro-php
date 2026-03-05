<?php
function css_defaultstyles_check_preconditions() {
	$ret = new Status();
	Load::components('systemupdateinstaller');
	$root = Load::get_module_dir('css.defaultstyles') . 'data/';
	/** @phpstan-ignore class.notFound */
	$ret->merge(SystemUpdateInstaller::copy_to_webroot($root, array('css'), SystemUpdateInstaller::COPY_OVERWRITE));
	return $ret;
}
