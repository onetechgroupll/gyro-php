<?php
/**
 * @defgroup Admin
 * @ingroup Modules
 *
 * Auto-Admin module.
 *
 * Django-Admin-Style CRUD interface generated from DAO model schemas.
 * Enable this module for a zero-config admin panel.
 *
 * Configuration (optional):
 *
 *   // Exclude sensitive tables
 *   AdminController::exclude_table('sessions');
 *
 *   // Or register only specific models
 *   AdminController::register_model('users', 'DAOUsers');
 *
 * @since 0.11
 */

// Load admin helpers
require_once dirname(__FILE__) . '/lib/helpers/adminhtml.cls.php';

// Load CLI model discovery (for auto-discovery)
if (class_exists('CLICommand', false)) {
	// CLI classes already loaded
} else {
	$cli_dir = GYRO_CORE_DIR . 'cli/';
	if (is_dir($cli_dir)) {
		if (file_exists($cli_dir . 'clicommand.cls.php')) {
			require_once $cli_dir . 'clicommand.cls.php';
		}
		if (file_exists($cli_dir . 'clitable.cls.php')) {
			require_once $cli_dir . 'clitable.cls.php';
		}
		$model_list_cmd = $cli_dir . 'commands/modellistcommand.cli.php';
		if (file_exists($model_list_cmd)) {
			require_once $model_list_cmd;
		}
	}
}

// Load and register the Admin controller
require_once dirname(__FILE__) . '/controller/admin.controller.php';
