<?php
/**
 * Gerenciador de Cache.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gerencia cache e transientes do plugin.
 */
class SMM_Cache_Manager {

	/**
	 * Prefixo para transientes.
	 *
	 * @var string
	 */
	const CACHE_PREFIX = 'smm_cache_';

	/**
	 * Define um valor em cache.
	 *
	 * @param string $key    Chave do cache.
	 * @param mixed  $value  Valor a armazenar.
	 * @param int    $expiration Tempo de expiração em segundos.
	 * @return bool
	 */
	public static function set( $key, $value, $expiration = HOUR_IN_SECONDS ) {
		if ( ! get_option( 'smm_enable_cache' ) ) {
			return false;
		}

		$cache_key = self::CACHE_PREFIX . $key;

		// Tentar usar Object Cache primeiro (Redis, Memcached, etc).
		if ( wp_cache_supports( 'add_multiple' ) ) {
			return wp_cache_set( $cache_key, $value, 'smm', $expiration );
		}

		// Fallback para Transients.
		return set_transient( $cache_key, $value, $expiration );
	}

	/**
	 * Obtém um valor do cache.
	 *
	 * @param string $key Chave do cache.
	 * @return mixed|false
	 */
	public static function get( $key ) {
		if ( ! get_option( 'smm_enable_cache' ) ) {
			return false;
		}

		$cache_key = self::CACHE_PREFIX . $key;

		// Tentar Object Cache primeiro.
		$value = wp_cache_get( $cache_key, 'smm' );

		if ( false !== $value ) {
			return $value;
		}

		// Fallback para Transients.
		return get_transient( $cache_key );
	}

	/**
	 * Deleta um valor do cache.
	 *
	 * @param string $key Chave do cache.
	 * @return bool
	 */
	public static function delete( $key ) {
		$cache_key = self::CACHE_PREFIX . $key;

		// Remover do Object Cache.
		wp_cache_delete( $cache_key, 'smm' );

		// Remover do Transients.
		return delete_transient( $cache_key );
	}

	/**
	 * Limpa todos os caches do plugin.
	 *
	 * @return void
	 */
	public static function flush() {
		global $wpdb;

		// Limpar transients.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%' . $wpdb->esc_like( self::CACHE_PREFIX ) . '%'
			)
		);

		// Limpar Object Cache.
		wp_cache_flush();
	}

	/**
	 * Armazena dados no banco de dados de cache.
	 *
	 * @param string $cache_key  Chave de cache.
	 * @param mixed  $cache_value Valor.
	 * @param int    $provider_id ID do provider.
	 * @param int    $expiration Tempo de expiração.
	 * @return int|false
	 */
	public static function store_db_cache( $cache_key, $cache_value, $provider_id = null, $expiration = HOUR_IN_SECONDS ) {
		global $wpdb;

		$expires_at = date( 'Y-m-d H:i:s', time() + $expiration );

		// Verificar se já existe.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}smm_sync_cache WHERE cache_key = %s",
				$cache_key
			)
		);

		if ( $existing ) {
			// Atualizar.
			return $wpdb->update(
				$wpdb->prefix . 'smm_sync_cache',
				array(
					'cache_value' => wp_json_encode( $cache_value ),
					'expires_at'  => $expires_at,
				),
				array( 'cache_key' => $cache_key ),
				array( '%s', '%s' ),
				array( '%s' )
			);
		}

		// Inserir novo.
		return $wpdb->insert(
			$wpdb->prefix . 'smm_sync_cache',
			array(
				'cache_key'   => $cache_key,
				'cache_value' => wp_json_encode( $cache_value ),
				'provider_id' => $provider_id,
				'expires_at'  => $expires_at,
			),
			array( '%s', '%s', '%d', '%s' )
		);
	}

	/**
	 * Recupera dados do cache do banco de dados.
	 *
	 * @param string $cache_key Chave de cache.
	 * @return mixed|null
	 */
	public static function get_db_cache( $cache_key ) {
		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT cache_value, expires_at FROM {$wpdb->prefix}smm_sync_cache WHERE cache_key = %s",
				$cache_key
			)
		);

		if ( ! $result ) {
			return null;
		}

		// Verificar se expirou.
		if ( strtotime( $result->expires_at ) < time() ) {
			self::delete_db_cache( $cache_key );
			return null;
		}

		return json_decode( $result->cache_value, true );
	}

	/**
	 * Deleta cache do banco de dados.
	 *
	 * @param string $cache_key Chave de cache.
	 * @return int|false
	 */
	public static function delete_db_cache( $cache_key ) {
		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . 'smm_sync_cache',
			array( 'cache_key' => $cache_key ),
			array( '%s' )
		);
	}

	/**
	 * Limpa cache expirado do banco de dados.
	 *
	 * @return int|false
	 */
	public static function cleanup_expired() {
		global $wpdb;

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}smm_sync_cache WHERE expires_at < %s",
				date( 'Y-m-d H:i:s' )
			)
		);
	}
}
