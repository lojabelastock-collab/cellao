<?php
/**
 * Classe para provider SmartPanel.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Providers
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implementação específica para SmartPanel.
 */
class SMM_Provider_SmartPanel extends SMM_Provider_Abstract {

	/**
	 * Slug do provider.
	 *
	 * @var string
	 */
	protected $slug = 'smartpanel';

	/**
	 * Nome do provider.
	 *
	 * @var string
	 */
	protected $name = 'SmartPanel';

	/**
	 * URL base da API.
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.smartpanel.com/v1/';

	/**
	 * Inicializa o provider SmartPanel.
	 *
	 * @return void
	 */
	protected function init() {
		// Inicialização específica do SmartPanel.
	}

	/**
	 * Sincroniza dados do SmartPanel.
	 *
	 * @return bool
	 */
	public function sync() {
		if ( ! $this->is_authenticated() ) {
			return false;
		}

		// Sincronizar produtos.
		$this->sync_products();

		// Sincronizar pedidos.
		$this->sync_orders();

		// Sincronizar clientes.
		$this->sync_customers();

		// Atualizar status de sincronização.
		$this->update_sync_status( 'completed' );

		return true;
	}

	/**
	 * Sincroniza produtos.
	 *
	 * @return void
	 */
	private function sync_products() {
		$response = $this->request( 'products', 'GET' );

		if ( ! $response['success'] ) {
			return;
		}

		$products = $response['data']['products'] ?? array();

		foreach ( $products as $product_data ) {
			$this->process_product( $product_data );
		}

		// Cache os produtos.
		SMM_Cache_Manager::set( 'smartpanel_products', $products, HOUR_IN_SECONDS );
	}

	/**
	 * Processa um produto.
	 *
	 * @param array $product_data Dados do produto.
	 * @return void
	 */
	private function process_product( $product_data ) {
		// Implementação específica de processamento de produto.
		do_action( 'smm_process_product', $product_data, $this->slug );
	}

	/**
	 * Sincroniza pedidos.
	 *
	 * @return void
	 */
	private function sync_orders() {
		$response = $this->request( 'orders', 'GET' );

		if ( ! $response['success'] ) {
			return;
		}

		$orders = $response['data']['orders'] ?? array();

		foreach ( $orders as $order_data ) {
			$this->process_order( $order_data );
		}

		// Cache os pedidos.
		SMM_Cache_Manager::set( 'smartpanel_orders', $orders, HOUR_IN_SECONDS );
	}

	/**
	 * Processa um pedido.
	 *
	 * @param array $order_data Dados do pedido.
	 * @return void
	 */
	private function process_order( $order_data ) {
		do_action( 'smm_process_order', $order_data, $this->slug );
	}

	/**
	 * Sincroniza clientes.
	 *
	 * @return void
	 */
	private function sync_customers() {
		$response = $this->request( 'customers', 'GET' );

		if ( ! $response['success'] ) {
			return;
		}

		$customers = $response['data']['customers'] ?? array();

		foreach ( $customers as $customer_data ) {
			$this->process_customer( $customer_data );
		}

		// Cache os clientes.
		SMM_Cache_Manager::set( 'smartpanel_customers', $customers, HOUR_IN_SECONDS );
	}

	/**
	 * Processa um cliente.
	 *
	 * @param array $customer_data Dados do cliente.
	 * @return void
	 */
	private function process_customer( $customer_data ) {
		do_action( 'smm_process_customer', $customer_data, $this->slug );
	}

	/**
	 * Atualiza o status de sincronização no banco de dados.
	 *
	 * @param string $status Status.
	 * @return void
	 */
	private function update_sync_status( $status = 'pending' ) {
		global $wpdb;

		if ( ! $this->id ) {
			return;
		}

		$wpdb->update(
			$wpdb->prefix . 'smm_providers',
			array(
				'last_sync'   => current_time( 'mysql' ),
				'sync_status' => $status,
			),
			array( 'id' => $this->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}
}
