<?php
function css_yaml4_check_preconditions() {
	$ret = new Status();
	Load::components('systemupdateinstaller');
	$root = Load::get_module_dir('css.yaml4') . 'data/' . Config::get_value(ConfigYAML4::VERSION) . '/';
	/** @phpstan-ignore class.notFound, class.notFound */
	$ret->merge(SystemUpdateInstaller::copy_to_webroot($root, array('yaml'), SystemUpdateInstaller::COPY_OVERWRITE));
	/** @phpstan-ignore class.notFound, class.notFound */
	$ret->merge(SystemUpdateInstaller::copy_to_webroot($root, array('css'), SystemUpdateInstaller::COPY_NO_REPLACE));
	return $ret;
}
