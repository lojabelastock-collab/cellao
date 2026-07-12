<?php
/**
 * Arquivo de configuração central do plugin.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configurações padrão do plugin.
 */
return array(
	'plugin' => array(
		'name'        => 'SMM WooCommerce Ultimate Integration',
		'version'     => SMM_PLUGIN_VERSION,
		'db_version'  => SMM_DB_VERSION,
		'basename'    => SMM_PLUGIN_BASENAME,
		'directory'   => SMM_PLUGIN_DIR,
		'url'         => SMM_PLUGIN_URL,
	),
	'api' => array(
		'namespace'        => 'smm/v1',
		'request_timeout'  => 30,
		'max_retries'      => 3,
		'enable_cache'     => true,
		'cache_expiration' => 3600,
	),
	'database' => array(
		'prefix' => 'smm_',
		'tables' => array(
			'api_logs'        => 'smm_api_logs',
			'providers'       => 'smm_providers',
			'sync_cache'      => 'smm_sync_cache',
			'scheduled_tasks' => 'smm_scheduled_tasks',
			'audit_log'       => 'smm_audit_log',
		),
	),
	'providers' => array(
		'enable_smartpanel'    => false,
		'enable_perfectpanel'  => false,
		'enable_panelmix'      => false,
	),
	'security' => array(
		'min_php_version'       => '8.0',
		'min_wordpress_version' => '6.0',
	),
	'features' => array(
		'levels'     => true,
		'points'     => true,
		'cashback'   => true,
		'favorites'  => true,
		'banners'    => true,
	),
);
