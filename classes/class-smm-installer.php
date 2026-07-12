<?php
/**
 * Classe de instalação e desativação do plugin.
 *
 * Gerencia criação de tabelas, limpeza de dados
 * e setup inicial do plugin.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Classes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Responsável pelos processos de instalação e desativação.
 */
class SMM_Installer {

	/**
	 * Executa rotina de ativação do plugin.
	 *
	 * @return void
	 */
	public static function activate() {
		// Verificar nível de permissão.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Criar tabelas do banco de dados.
		self::create_database_tables();

		// Inicializar opções padrão.
		self::init_default_options();

		// Criar diretórios necessários.
		self::create_required_directories();

		// Hook customizado de ativação.
		do_action( 'smm_plugin_activated' );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Executa rotina de desativação do plugin.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Verificar nível de permissão.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Hook customizado de desativação.
		do_action( 'smm_plugin_deactivated' );

		// Limpar scheduled events.
		self::clear_scheduled_events();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Cria as tabelas customizadas do plugin.
	 *
	 * @return void
	 */
	private static function create_database_tables() {
		global $wpdb;

		// Obter charset e collation do WordPress.
		$charset_collate = $wpdb->get_charset_collate();

		// Preparar SQL para criação de tabelas.
		$sqls = array();

		// Tabela de logs de API.
		$sqls[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smm_api_logs (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			provider_id VARCHAR(100) NOT NULL,
			provider_name VARCHAR(100) NOT NULL,
			endpoint VARCHAR(255) NOT NULL,
			method VARCHAR(10) NOT NULL,
			request_data LONGTEXT,
			response_data LONGTEXT,
			http_code INT(11),
			error_message LONGTEXT,
			execution_time FLOAT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_provider (provider_id),
			INDEX idx_created_at (created_at),
			INDEX idx_http_code (http_code)
		) {$charset_collate};";

		// Tabela de relação entre providers.
		$sqls[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smm_providers (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			provider_type VARCHAR(100) NOT NULL,
			provider_name VARCHAR(255) NOT NULL,
			provider_slug VARCHAR(100) NOT NULL UNIQUE,
			is_active TINYINT(1) DEFAULT 0,
			configuration LONGTEXT,
			last_sync DATETIME,
			sync_status VARCHAR(50),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_provider_type (provider_type),
			INDEX idx_is_active (is_active),
			INDEX idx_last_sync (last_sync)
		) {$charset_collate};";

		// Tabela de cache/sincronização.
		$sqls[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smm_sync_cache (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			cache_key VARCHAR(255) NOT NULL UNIQUE,
			cache_value LONGTEXT,
			provider_id BIGINT(20) UNSIGNED,
			expires_at DATETIME,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_cache_key (cache_key),
			INDEX idx_provider_id (provider_id),
			INDEX idx_expires_at (expires_at)
		) {$charset_collate};";

		// Tabela de tarefas agendadas.
		$sqls[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smm_scheduled_tasks (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			task_name VARCHAR(100) NOT NULL,
			task_type VARCHAR(50) NOT NULL,
			provider_id BIGINT(20) UNSIGNED,
			schedule_time DATETIME NOT NULL,
			status VARCHAR(50) DEFAULT 'pending',
			last_executed DATETIME,
			next_execution DATETIME,
			retry_count INT(11) DEFAULT 0,
			max_retries INT(11) DEFAULT 3,
			error_log LONGTEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_task_name (task_name),
			INDEX idx_status (status),
			INDEX idx_schedule_time (schedule_time),
			INDEX idx_provider_id (provider_id)
		) {$charset_collate};";

		// Tabela de auditoria de alterações.
		$sqls[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smm_audit_log (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT(20) UNSIGNED,
			action VARCHAR(100) NOT NULL,
			object_type VARCHAR(50),
			object_id BIGINT(20) UNSIGNED,
			old_value LONGTEXT,
			new_value LONGTEXT,
			user_ip VARCHAR(45),
			user_agent LONGTEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_user_id (user_id),
			INDEX idx_action (action),
			INDEX idx_object_type (object_type),
			INDEX idx_created_at (created_at)
		) {$charset_collate};";

		// Executar queries de criação de tabelas.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( $sqls as $sql ) {
			dbDelta( $sql );
		}

		// Salvar versão do banco de dados.
		update_option( 'smm_db_version', SMM_DB_VERSION );
	}

	/**
	 * Inicializa as opções padrão do plugin.
	 *
	 * @return void
	 */
	private static function init_default_options() {
		$default_options = array(
			'smm_plugin_enabled'         => 1,
			'smm_log_api_requests'       => 1,
			'smm_enable_async_processing' => 1,
			'smm_sync_interval'          => 3600, // 1 hora.
			'smm_max_retries'            => 3,
			'smm_request_timeout'        => 30,
			'smm_enable_cache'           => 1,
			'smm_cache_expiration'       => 3600,
			'smm_debug_mode'             => 0,
		);

		foreach ( $default_options as $option_name => $option_value ) {
			if ( ! get_option( $option_name ) ) {
				add_option( $option_name, $option_value );
			}
		}
	}

	/**
	 * Cria diretórios necessários para o plugin.
	 *
	 * @return void
	 */
	private static function create_required_directories() {
		$directories = array(
			SMM_PLUGIN_DIR . 'languages',
			SMM_PLUGIN_DIR . 'assets/css',
			SMM_PLUGIN_DIR . 'assets/js',
			SMM_PLUGIN_DIR . 'assets/images',
			SMM_PLUGIN_DIR . 'uploads',
			SMM_PLUGIN_DIR . 'cache',
			SMM_PLUGIN_DIR . 'logs',
		);

		foreach ( $directories as $dir ) {
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );
			}
		}
	}

	/**
	 * Limpa eventos agendados ao desativar.
	 *
	 * @return void
	 */
	private static function clear_scheduled_events() {
		// Remover todos os crons do plugin.
		wp_clear_scheduled_hook( 'smm_sync_providers' );
		wp_clear_scheduled_hook( 'smm_cleanup_logs' );
		wp_clear_scheduled_hook( 'smm_cleanup_cache' );
	}
}
