<?php
/**
 * Engine de Sincronização.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Background
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gerencia sincronização de dados entre providers e WooCommerce.
 */
class SMM_Sync_Engine {

	/**
	 * Instância única da classe.
	 *
	 * @var SMM_Sync_Engine
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
	 * @return SMM_Sync_Engine
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
		add_action( 'smm_sync_providers', array( $this, 'sync_all_providers' ) );
		
		// Agendar sincronização automática.
		if ( ! wp_next_scheduled( 'smm_sync_providers' ) ) {
			wp_schedule_event( time(), 'hourly', 'smm_sync_providers' );
		}
	}

	/**
	 * Sincroniza todos os providers ativos.
	 *
	 * @return void
	 */
	public function sync_all_providers() {
		$providers = SMM_Providers_Manager::get_providers_from_db();

		foreach ( $providers as $provider ) {
			$this->sync_provider( $provider->provider_slug );
		}

		do_action( 'smm_sync_completed' );
	}

	/**
	 * Sincroniza um provider específico.
	 *
	 * @param string $provider_slug Slug do provider.
	 * @return bool
	 */
	public function sync_provider( $provider_slug ) {
		$provider = SMM_Providers_Manager::get_provider_from_db( $provider_slug );

		if ( ! $provider ) {
			return false;
		}

		// Atualizar status para "syncing".
		$this->update_sync_status( $provider_slug, 'syncing' );

		try {
			// Autenticar.
			if ( ! $provider->authenticate() ) {
				$this->update_sync_status( $provider_slug, 'failed' );
				return false;
			}

			// Executar sincronização.
			$result = $provider->sync();

			if ( $result ) {
				$this->update_sync_status( $provider_slug, 'completed' );
			} else {
				$this->update_sync_status( $provider_slug, 'failed' );
			}

			return $result;
		} catch ( Exception $e ) {
			$this->update_sync_status( $provider_slug, 'failed' );
			return false;
		}
	}

	/**
	 * Sincroniza um provider de forma assíncrona.
	 *
	 * @param string $provider_slug Slug do provider.
	 * @return void
	 */
	public function schedule_sync( $provider_slug ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'smm_scheduled_tasks',
			array(
				'task_name'     => 'sync_provider',
				'task_type'     => 'sync',
				'provider_id'   => null,
				'schedule_time' => current_time( 'mysql' ),
				'status'        => 'pending',
			),
			array( '%s', '%s', '%d', '%s', '%s' )
		);

		// Disparar a sincronização em background.
		if ( get_option( 'smm_enable_async_processing' ) ) {
			wp_remote_post(
				rest_url( 'smm/v1/sync/provider' ),
				array(
					'blocking'  => false,
					'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
					'body'      => array(
						'provider_slug' => $provider_slug,
					),
				)
			);
		}
	}

	/**
	 * Atualiza o status de sincronização.
	 *
	 * @param string $provider_slug Slug do provider.
	 * @param string $status        Status.
	 * @return void
	 */
	private function update_sync_status( $provider_slug, $status = 'pending' ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'smm_providers',
			array(
				'sync_status' => $status,
				'last_sync'   => 'completed' === $status ? current_time( 'mysql' ) : null,
			),
			array( 'provider_slug' => $provider_slug ),
			array( '%s', '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Obtém o status de sincronização.
	 *
	 * @param string $provider_slug Slug do provider.
	 * @return array
	 */
	public function get_sync_status( $provider_slug ) {
		global $wpdb;

		$provider = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT sync_status, last_sync FROM {$wpdb->prefix}smm_providers WHERE provider_slug = %s",
				$provider_slug
			)
		);

		if ( ! $provider ) {
			return array(
				'status'    => 'unknown',
				'last_sync' => null,
			);
		}

		return array(
			'status'    => $provider->sync_status,
			'last_sync' => $provider->last_sync,
		);
	}
}
