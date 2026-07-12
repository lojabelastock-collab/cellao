<?php
/**
 * Classe de verificação de segurança do plugin.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Core
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifica dependências e configurações de segurança.
 */
class SMM_Security_Check {

	/**
	 * Verifica se WooCommerce está ativo.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' ) && class_exists( 'WC_Product' );
	}

	/**
	 * Verifica a versão mínima do WordPress.
	 *
	 * @return bool
	 */
	public static function check_wordpress_version() {
		global $wp_version;
		return version_compare( $wp_version, '6.0', '>=' );
	}

	/**
	 * Verifica a versão mínima do PHP.
	 *
	 * @return bool
	 */
	public static function check_php_version() {
		return version_compare( PHP_VERSION, '8.0', '>=' );
	}

	/**
	 * Sanitiza e valida entrada de usuário.
	 *
	 * @param mixed  $input Entrada a sanitizar.
	 * @param string $type  Tipo de sanitização: 'text', 'email', 'url', 'int', 'array'.
	 * @return mixed
	 */
	public static function sanitize_input( $input, $type = 'text' ) {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $input );
			case 'url':
				return esc_url( $input );
			case 'int':
				return intval( $input );
			case 'array':
				return array_map( 'sanitize_text_field', (array) $input );
			default:
				return sanitize_text_field( $input );
		}
	}

	/**
	 * Valida nonce.
	 *
	 * @param string $nonce Nome do nonce.
	 * @param string $action Ação do nonce.
	 * @return bool
	 */
	public static function verify_nonce( $nonce, $action = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return isset( $_REQUEST[ $nonce ] ) && wp_verify_nonce( $_REQUEST[ $nonce ], $action );
	}

	/**
	 * Verifica se o usuário tem capability.
	 *
	 * @param string $capability Capability a verificar.
	 * @return bool
	 */
	public static function check_capability( $capability = 'manage_options' ) {
		return current_user_can( $capability );
	}
}
