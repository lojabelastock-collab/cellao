<?php
/**
 * Classe abstrata base para providers.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Providers
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe abstrata que implementa funcionalidades comuns de providers.
 */
abstract class SMM_Provider_Abstract implements SMM_Provider_Interface {

	/**
	 * ID do provider no banco de dados.
	 *
	 * @var int
	 */
	protected $id = null;

	/**
	 * Slug do provider.
	 *
	 * @var string
	 */
	protected $slug = '';

	/**
	 * Nome do provider.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * URL base da API.
	 *
	 * @var string
	 */
	protected $api_url = '';

	/**
	 * Chave de API.
	 *
	 * @var string
	 */
	protected $api_key = '';

	/**
	 * Segredo da API.
	 *
	 * @var string
	 */
	protected $api_secret = '';

	/**
	 * Status de autenticação.
	 *
	 * @var bool
	 */
	protected $authenticated = false;

	/**
	 * Timeout para requisições.
	 *
	 * @var int
	 */
	protected $request_timeout = 30;

	/**
	 * Construtor.
	 *
	 * @param int   $id            ID do provider.
	 * @param array $configuration Configuração do provider.
	 */
	public function __construct( $id = null, $configuration = array() ) {
		$this->id = $id;

		if ( ! empty( $configuration ) ) {
			$this->set_configuration( $configuration );
		}

		$this->init();
	}

	/**
	 * Inicializa o provider.
	 *
	 * @return void
	 */
	protected function init() {
		// Método para ser sobrescrito pelas subclasses.
	}

	/**
	 * Define a configuração do provider.
	 *
	 * @param array $configuration Configuração.
	 * @return void
	 */
	public function set_configuration( $configuration ) {
		if ( isset( $configuration['api_key'] ) ) {
			$this->api_key = sanitize_text_field( $configuration['api_key'] );
		}

		if ( isset( $configuration['api_secret'] ) ) {
			$this->api_secret = sanitize_text_field( $configuration['api_secret'] );
		}
	}

	/**
	 * Obtém o nome do provider.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Obtém o slug do provider.
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Obtém o ID do provider.
	 *
	 * @return int|null
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Obtém a URL base da API.
	 *
	 * @return string
	 */
	public function get_api_url() {
		return $this->api_url;
	}

	/**
	 * Valida as credenciais do provider.
	 *
	 * @param array $credentials Credenciais a validar.
	 * @return bool
	 */
	public function validate_credentials( $credentials ) {
		if ( empty( $credentials['api_key'] ) ) {
			return false;
		}

		if ( empty( $credentials['api_secret'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Autentica com o provider.
	 *
	 * @return bool
	 */
	public function authenticate() {
		if ( empty( $this->api_key ) || empty( $this->api_secret ) ) {
			return false;
		}

		$this->authenticated = true;
		return true;
	}

	/**
	 * Verifica se está autenticado.
	 *
	 * @return bool
	 */
	public function is_authenticated() {
		return $this->authenticated;
	}

	/**
	 * Faz uma requisição à API.
	 *
	 * @param string $endpoint Endpoint da API.
	 * @param string $method   Método HTTP.
	 * @param array  $data     Dados a enviar.
	 * @return array
	 */
	public function request( $endpoint, $method = 'GET', $data = array() ) {
		if ( ! $this->is_authenticated() ) {
			return $this->error_response( 'Not authenticated' );
		}

		$url = $this->api_url . $endpoint;

		$args = array(
			'method'  => strtoupper( $method ),
			'timeout' => $this->request_timeout,
			'headers' => $this->get_request_headers(),
		);

		if ( ! empty( $data ) && 'GET' !== strtoupper( $method ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		$start_time = microtime( true );

		$response = wp_remote_request( esc_url( $url ), $args );

		$execution_time = microtime( true ) - $start_time;

		if ( is_wp_error( $response ) ) {
			$this->log_api_request( $endpoint, $method, $data, null, 0, $response->get_error_message(), $execution_time );
			return $this->error_response( $response->get_error_message() );
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = wp_remote_retrieve_body( $response );

		$response_data = json_decode( $body, true );

		$this->log_api_request( $endpoint, $method, $data, $body, $http_code, null, $execution_time );

		if ( $http_code >= 400 ) {
			return $this->error_response( 'HTTP ' . $http_code, $response_data );
		}

		return $this->success_response( $response_data );
	}

	/**
	 * Obtém os headers da requisição.
	 *
	 * @return array
	 */
	protected function get_request_headers() {
		return array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->api_key,
		);
	}

	/**
	 * Registra a requisição de API.
	 *
	 * @param string $endpoint        Endpoint.
	 * @param string $method          Método HTTP.
	 * @param array  $request_data    Dados da requisição.
	 * @param string $response_data   Dados da resposta.
	 * @param int    $http_code       Código HTTP.
	 * @param string $error_message   Mensagem de erro.
	 * @param float  $execution_time  Tempo de execução.
	 * @return void
	 */
	private function log_api_request( $endpoint, $method, $request_data, $response_data, $http_code, $error_message, $execution_time ) {
		if ( ! get_option( 'smm_log_api_requests' ) ) {
			return;
		}

		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'smm_api_logs',
			array(
				'provider_id'    => $this->id,
				'provider_name'  => $this->name,
				'endpoint'       => $endpoint,
				'method'         => $method,
				'request_data'   => wp_json_encode( $request_data ),
				'response_data'  => $response_data,
				'http_code'      => $http_code,
				'error_message'  => $error_message,
				'execution_time' => $execution_time,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%f' )
		);
	}

	/**
	 * Retorna resposta de sucesso.
	 *
	 * @param mixed $data Dados da resposta.
	 * @return array
	 */
	protected function success_response( $data = null ) {
		return array(
			'success' => true,
			'data'    => $data,
			'error'   => null,
		);
	}

	/**
	 * Retorna resposta de erro.
	 *
	 * @param string $error Mensagem de erro.
	 * @param mixed  $data  Dados adicionais.
	 * @return array
	 */
	protected function error_response( $error = '', $data = null ) {
		return array(
			'success' => false,
			'data'    => $data,
			'error'   => $error,
		);
	}

	/**
	 * Sincroniza dados do provider.
	 *
	 * @return bool
	 */
	public function sync() {
		// Método abstrato a ser implementado pelas subclasses.
		return false;
	}
}
