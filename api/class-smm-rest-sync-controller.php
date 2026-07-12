<?php
/**
 * Endpoints REST API para sincronização.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage API
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controlador de endpoints de sincronização.
 */
class SMM_REST_Sync_Controller extends WP_REST_Controller {

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'smm/v1';

	/**
	 * Base route.
	 *
	 * @var string
	 */
	protected $rest_base = 'sync';

	/**
	 * Registra as rotas.
	 *
	 * @return void
	 */
	public function register_routes() {
		// POST /smm/v1/sync/all.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/all',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'sync_all' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// POST /smm/v1/sync/provider.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/provider',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'sync_single_provider' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// GET /smm/v1/sync/status.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sync_status' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// GET /smm/v1/sync/logs.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/logs',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sync_logs' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);
	}

	/**
	 * Sincroniza todos os providers.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function sync_all( $request ) {
		$sync_engine = SMM_Sync_Engine::get_instance();
		$sync_engine->sync_all_providers();

		return new WP_REST_Response(
			array( 'message' => 'Full sync initiated' ),
			200
		);
	}

	/**
	 * Sincroniza um provider específico.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function sync_single_provider( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params['provider_slug'] ) ) {
			return new WP_REST_Response(
				array( 'message' => 'Missing provider_slug parameter' ),
				400
			);
		}

		$sync_engine = SMM_Sync_Engine::get_instance();
		$result      = $sync_engine->sync_provider( sanitize_text_field( $params['provider_slug'] ) );

		if ( ! $result ) {
			return new WP_REST_Response(
				array( 'message' => 'Sync failed' ),
				500
			);
		}

		return new WP_REST_Response(
			array( 'message' => 'Provider sync initiated' ),
			200
		);
	}

	/**
	 * Obtém status de sincronização geral.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_sync_status( $request ) {
		global $wpdb;

		$providers = $wpdb->get_results(
			"SELECT provider_slug, sync_status, last_sync FROM {$wpdb->prefix}smm_providers"
		);

		return new WP_REST_Response(
			array(
				'providers' => $providers,
				'timestamp' => current_time( 'mysql' ),
			),
			200
		);
	}

	/**
	 * Obtém logs de sincronização.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_sync_logs( $request ) {
		global $wpdb;

		$page     = intval( $request->get_param( 'page' ) ) ?: 1;
		$per_page = intval( $request->get_param( 'per_page' ) ) ?: 20;
		$offset   = ( $page - 1 ) * $per_page;

		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}smm_api_logs ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}smm_api_logs" );

		return new WP_REST_Response(
			array(
				'logs'      => $logs,
				'total'     => intval( $total ),
				'page'      => $page,
				'per_page'  => $per_page,
				'max_pages' => ceil( $total / $per_page ),
			),
			200
		);
	}

	/**
	 * Verifica permissões.
	 *
	 * @return bool
	 */
	public function check_permissions() {
		return current_user_can( 'manage_options' );
	}
}
