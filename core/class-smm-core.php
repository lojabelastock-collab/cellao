<?php
/**
 * Classe Core principal do plugin SMM WooCommerce Ultimate Integration.
 *
 * Implementa o padrão Singleton e gerencia a inicialização
 * de todos os componentes do plugin.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Core
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe singleton responsável pela inicialização do plugin.
 */
class SMM_Core {

	/**
	 * Instância única da classe.
	 *
	 * @var SMM_Core
	 */
	private static $instance = null;

	/**
	 * Indica se o plugin foi inicializado.
	 *
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Construtor privado para impedir instanciação direta.
	 */
	private function __construct() {
		// Constructor privado para Singleton.
	}

	/**
	 * Obtém a instância única da classe (Singleton).
	 *
	 * @return SMM_Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Inicializa o plugin e seus componentes.
	 *
	 * @return void
	 */
	private function init() {
		if ( $this->initialized ) {
			return;
		}

		// Carregar as traduções do plugin.
		$this->load_textdomain();

		// Carregar classes principais.
		$this->load_core_classes();

		// Inicializar providers.
		$this->init_providers();

		// Inicializar sincronização.
		$this->init_sync();

		// Inicializar componentes principais.
		$this->init_admin();
		$this->init_api();
		$this->init_frontend();
		$this->init_background_processes();

		// Hook customizado para extensibilidade.
		do_action( 'smm_plugin_initialized' );

		$this->initialized = true;
	}

	/**
	 * Carrega as traduções do plugin.
	 *
	 * @return void
	 */
	private function load_textdomain() {
		load_plugin_textdomain(
			'smm-woocommerce-ultimate-integration',
			false,
			dirname( SMM_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Carrega classes principais do plugin.
	 *
	 * @return void
	 */
	private function load_core_classes() {
		require_once SMM_PLUGIN_DIR . 'includes/class-smm-cache-manager.php';
		require_once SMM_PLUGIN_DIR . 'providers/interface-smm-provider.php';
		require_once SMM_PLUGIN_DIR . 'providers/class-smm-provider-abstract.php';
		require_once SMM_PLUGIN_DIR . 'providers/class-smm-providers-manager.php';
		require_once SMM_PLUGIN_DIR . 'background/class-smm-sync-engine.php';
	}

	/**
	 * Inicializa providers.
	 *
	 * @return void
	 */
	private function init_providers() {
		// Registrar providers padrão.
		require_once SMM_PLUGIN_DIR . 'providers/class-smm-provider-smartpanel.php';
		SMM_Providers_Manager::register_provider( 'smartpanel', 'SMM_Provider_SmartPanel' );

		// Hook para registrar providers customizados.
		do_action( 'smm_register_providers' );
	}

	/**
	 * Inicializa o sistema de sincronização.
	 *
	 * @return void
	 */
	private function init_sync() {
		SMM_Sync_Engine::get_instance();
	}

	/**
	 * Inicializa componentes administrativos.
	 *
	 * @return void
	 */
	private function init_admin() {
		if ( is_admin() ) {
			require_once SMM_PLUGIN_DIR . 'admin/class-smm-admin.php';
			SMM_Admin::get_instance();
		}
	}

	/**
	 * Inicializa endpoints REST API.
	 *
	 * @return void
	 */
	private function init_api() {
		require_once SMM_PLUGIN_DIR . 'api/class-smm-rest-controller.php';
		SMM_REST_Controller::get_instance();
	}

	/**
	 * Inicializa componentes de frontend.
	 *
	 * @return void
	 */
	private function init_frontend() {
		if ( ! is_admin() ) {
			require_once SMM_PLUGIN_DIR . 'frontend/class-smm-frontend.php';
			SMM_Frontend::get_instance();
		}
	}

	/**
	 * Inicializa processamento em background.
	 *
	 * @return void
	 */
	private function init_background_processes() {
		require_once SMM_PLUGIN_DIR . 'background/class-smm-background-processor.php';
		SMM_Background_Processor::get_instance();
	}

	/**
	 * Obtém a versão do plugin.
	 *
	 * @return string
	 */
	public static function get_version() {
		return SMM_PLUGIN_VERSION;
	}

	/**
	 * Verifica se o plugin está ativo.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return function_exists( 'is_plugin_active' ) && is_plugin_active( SMM_PLUGIN_BASENAME );
	}

	/**
	 * Previne clonagem da instância Singleton.
	 *
	 * @return void
	 */
	private function __clone() {
		// Prevent cloning.
	}

	/**
	 * Previne desserialização da instância Singleton.
	 *
	 * @return void
	 */
	public function __wakeup() {
		// Prevent unserializing.
	}
}
