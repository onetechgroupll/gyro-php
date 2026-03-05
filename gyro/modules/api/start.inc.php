<?php
/**
 * @defgroup API
 * @ingroup Modules
 *
 * Auto-REST-API module.
 *
 * Automatically generates REST endpoints for all DAO models.
 * Enable this module to expose your models as a JSON API.
 *
 * Configuration (optional, in your app's enabled.inc.php or .env):
 *
 *   // Exclude sensitive tables from the API
 *   RestApiController::exclude_table('sessions');
 *   RestApiController::exclude_table('cacheentries');
 *
 *   // Or explicitly register only certain models
 *   RestApiController::register_model('users', 'DAOUsers');
 *
 * @since 0.9
 */

// Load JSON response helper
require_once dirname(__FILE__) . '/lib/helpers/jsonresponse.cls.php';

// Load CLI model discovery (for auto-discovery)
if (class_exists('CLICommand', false)) {
	// CLI classes already loaded
} else {
	// Load CLI command base + model discovery for auto-discovery
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

// Load and register the REST API controller
require_once dirname(__FILE__) . '/controller/restapi.controller.php';
