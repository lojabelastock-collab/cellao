<?php
/**
 * Classe principal para componentes de frontend.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Frontend
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gerencia componentes de frontend do plugin.
 */
class SMM_Frontend {

	/**
	 * Instância única da classe.
	 *
	 * @var SMM_Frontend
	 */
	private static $instance = null;

	/**
	 * Construtor privado.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Obtém instância única.
	 *
	 * @return SMM_Frontend
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Inicializa hooks e filtros.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Carrega assets do frontend.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'smm-frontend', SMM_PLUGIN_URL . 'assets/css/frontend.css', array(), SMM_PLUGIN_VERSION );
		wp_enqueue_script( 'smm-frontend', SMM_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), SMM_PLUGIN_VERSION, true );
	}
}
