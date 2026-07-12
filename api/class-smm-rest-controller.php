<?php
/**
 * Controlador REST API do plugin.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage API
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gerencia endpoints customizados da REST API.
 */
class SMM_REST_Controller {

	/**
	 * Instância única da classe.
	 *
	 * @var SMM_REST_Controller
	 */
	private static $instance = null;

	/**
	 * Namespace da API.
	 *
	 * @var string
	 */
	private $namespace = 'smm/v1';

	/**
	 * Construtor privado.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Obtém instância única.
	 *
	 * @return SMM_REST_Controller
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Inicializa hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registra rotas da REST API.
	 *
	 * @return void
	 */
	public function register_routes() {
		// Endpoints serão registrados no Passo 2.
	}

	/**
	 * Obtém o namespace da API.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return $this->namespace;
	}
}
