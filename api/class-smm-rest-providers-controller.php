<?php
/**
 * Endpoints REST API do plugin.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage API
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controlador de endpoints de providers.
 */
class SMM_REST_Providers_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'providers';

	/**
	 * Registra as rotas.
	 *
	 * @return void
	 */
	public function register_routes() {
		// GET /smm/v1/providers.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_providers' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_provider' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
			)
		);

		// GET /smm/v1/providers/:slug.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<slug>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_provider' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_provider' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_provider' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
			)
		);

		// POST /smm/v1/providers/:slug/sync.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<slug>[a-zA-Z0-9_-]+)/sync',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'sync_provider' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);

		// GET /smm/v1/providers/:slug/status.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<slug>[a-zA-Z0-9_-]+)/status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sync_status' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Obtém lista de providers.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_providers( $request ) {
		$providers = SMM_Providers_Manager::get_providers_from_db();

		return new WP_REST_Response( $providers, 200 );
	}

	/**
	 * Obtém um provider específico.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_provider( $request ) {
		$slug = $request['slug'];

		global $wpdb;

		$provider = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}smm_providers WHERE provider_slug = %s",
				$slug
			)
		);

		if ( ! $provider ) {
			return new WP_REST_Response(
				array( 'message' => 'Provider not found' ),
				404
			);
		}

		return new WP_REST_Response( $provider, 200 );
	}

	/**
	 * Cria um novo provider.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function create_provider( $request ) {
		$params = $request->get_json_params();

		// Validar parâmetros.
		if ( empty( $params['slug'] ) || empty( $params['name'] ) ) {
			return new WP_REST_Response(
				array( 'message' => 'Missing required parameters' ),
				400
			);
		}

		$id = SMM_Providers_Manager::save_provider(
			sanitize_text_field( $params['slug'] ),
			sanitize_text_field( $params['name'] ),
			sanitize_text_field( $params['type'] ?? 'custom' ),
			$params['configuration'] ?? array()
		);

		if ( ! $id ) {
			return new WP_REST_Response(
				array( 'message' => 'Failed to create provider' ),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'id'      => $id,
				'message' => 'Provider created successfully',
			),
			201
		);
	}

	/**
	 * Atualiza um provider.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_provider( $request ) {
		$slug   = $request['slug'];
		$params = $request->get_json_params();

		$id = SMM_Providers_Manager::save_provider(
			$slug,
			sanitize_text_field( $params['name'] ?? '' ),
			sanitize_text_field( $params['type'] ?? '' ),
			$params['configuration'] ?? array()
		);

		if ( ! $id ) {
			return new WP_REST_Response(
				array( 'message' => 'Failed to update provider' ),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'id'      => $id,
				'message' => 'Provider updated successfully',
			),
			200
		);
	}

	/**
	 * Deleta um provider.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function delete_provider( $request ) {
		$slug = $request['slug'];

		global $wpdb;

		$result = $wpdb->delete(
			$wpdb->prefix . 'smm_providers',
			array( 'provider_slug' => $slug ),
			array( '%s' )
		);

		if ( ! $result ) {
			return new WP_REST_Response(
				array( 'message' => 'Failed to delete provider' ),
				500
			);
		}

		return new WP_REST_Response(
			array( 'message' => 'Provider deleted successfully' ),
			200
		);
	}

	/**
	 * Sincroniza um provider.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function sync_provider( $request ) {
		$slug = $request['slug'];

		$sync_engine = SMM_Sync_Engine::get_instance();
		$result      = $sync_engine->sync_provider( $slug );

		if ( ! $result ) {
			return new WP_REST_Response(
				array( 'message' => 'Failed to sync provider' ),
				500
			);
		}

		return new WP_REST_Response(
			array( 'message' => 'Provider sync started' ),
			200
		);
	}

	/**
	 * Obtém o status de sincronização de um provider.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_sync_status( $request ) {
		$slug = $request['slug'];

		$sync_engine = SMM_Sync_Engine::get_instance();
		$status      = $sync_engine->get_sync_status( $slug );

		return new WP_REST_Response( $status, 200 );
	}

	/**
	 * Verifica permissão para ler items.
	 *
	 * @return bool
	 */
	public function get_items_permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Verifica permissão para ler um item.
	 *
	 * @return bool
	 */
	public function get_item_permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Verifica permissão para criar items.
	 *
	 * @return bool
	 */
	public function create_item_permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Verifica permissão para atualizar items.
	 *
	 * @return bool
	 */
	public function update_item_permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Verifica permissão para deletar items.
	 *
	 * @return bool
	 */
	public function delete_item_permissions_check() {
		return current_user_can( 'manage_options' );
	}
}
