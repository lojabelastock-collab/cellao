<?php
/**
 * Classe principal para painel administrativo.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Admin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gerencia componentes administrativos do plugin.
 */
class SMM_Admin {

	/**
	 * Instância única da classe.
	 *
	 * @var SMM_Admin
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
	 * @return SMM_Admin
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
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Registra menu administrativo.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			esc_html__( 'SMM WooCommerce', 'smm-woocommerce-ultimate-integration' ),
			esc_html__( 'SMM Integration', 'smm-woocommerce-ultimate-integration' ),
			'manage_options',
			'smm-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-share-alt2',
			56
		);
	}

	/**
	 * Renderiza dashboard principal.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'smm-woocommerce-ultimate-integration' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'SMM WooCommerce Ultimate Integration', 'smm-woocommerce-ultimate-integration' ); ?></h1>
			<p><?php esc_html_e( 'Dashboard principal do plugin (em desenvolvimento).', 'smm-woocommerce-ultimate-integration' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Carrega assets do admin.
	 *
	 * @param string $hook Página atual.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_smm-dashboard' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'smm-admin', SMM_PLUGIN_URL . 'assets/css/admin.css', array(), SMM_PLUGIN_VERSION );
		wp_enqueue_script( 'smm-admin', SMM_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), SMM_PLUGIN_VERSION, true );
	}
}
