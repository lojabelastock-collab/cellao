<?php
/**
 * Classe para processamento em background.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Background
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gerencia processamento assíncrono e CRON.
 */
class SMM_Background_Processor {

	/**
	 * Instância única da classe.
	 *
	 * @var SMM_Background_Processor
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
	 * @return SMM_Background_Processor
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
		add_action( 'smm_sync_providers', array( $this, 'sync_providers' ) );
		add_action( 'smm_cleanup_logs', array( $this, 'cleanup_logs' ) );
		add_action( 'smm_cleanup_cache', array( $this, 'cleanup_cache' ) );

		// Agendar tarefas de limpeza.
		if ( ! wp_next_scheduled( 'smm_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'smm_cleanup_logs' );
		}

		if ( ! wp_next_scheduled( 'smm_cleanup_cache' ) ) {
			wp_schedule_event( time(), 'daily', 'smm_cleanup_cache' );
		}
	}

	/**
	 * Sincroniza providers com APIs externas.
	 *
	 * @return void
	 */
	public function sync_providers() {
		$sync_engine = SMM_Sync_Engine::get_instance();
		$sync_engine->sync_all_providers();
	}

	/**
	 * Limpa logs antigos.
	 *
	 * @return void
	 */
	public function cleanup_logs() {
		global $wpdb;

		// Manter apenas logs dos últimos 30 dias.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}smm_api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				30
			)
		);

		do_action( 'smm_logs_cleaned' );
	}

	/**
	 * Limpa cache expirado.
	 *
	 * @return void
	 */
	public function cleanup_cache() {
		// Limpar cache expirado do banco de dados.
		SMM_Cache_Manager::cleanup_expired();

		// Limpar transientes expirados.
		SMM_Cache_Manager::flush();

		do_action( 'smm_cache_cleaned' );
	}
}
