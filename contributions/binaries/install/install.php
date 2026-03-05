<?php
//Copy controller 
function binaries_install() {
	$ret = new Status();
	Load::components('systemupdateinstaller');
	/** @phpstan-ignore class.notFound, class.notFound */
	$ret->merge(
		SystemUpdateInstaller::copy_file_to_app(
			dirname(__FILE__) . '/binaries.controller.php.example',
			'controller/binaries.controller.php',
			SystemUpdateInstaller::COPY_NO_REPLACE
		)
	);
	return $ret;	
}