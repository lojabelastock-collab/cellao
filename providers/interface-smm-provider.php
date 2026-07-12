<?php
/**
 * Interface base para providers.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Providers
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface que define a estrutura de um provider.
 */
interface SMM_Provider_Interface {

	/**
	 * Obtém o nome do provider.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Obtém o slug do provider.
	 *
	 * @return string
	 */
	public function get_slug();

	/**
	 * Obtém a URL base da API.
	 *
	 * @return string
	 */
	public function get_api_url();

	/**
	 * Valida as credenciais do provider.
	 *
	 * @param array $credentials Credenciais a validar.
	 * @return bool
	 */
	public function validate_credentials( $credentials );

	/**
	 * Autentica com o provider.
	 *
	 * @return bool
	 */
	public function authenticate();

	/**
	 * Faz uma requisição à API.
	 *
	 * @param string $endpoint Endpoint da API.
	 * @param string $method   Método HTTP.
	 * @param array  $data     Dados a enviar.
	 * @return array
	 */
	public function request( $endpoint, $method = 'GET', $data = array() );

	/**
	 * Sincroniza dados do provider.
	 *
	 * @return bool
	 */
	public function sync();
}
