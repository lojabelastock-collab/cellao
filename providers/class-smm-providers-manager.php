<?php
/**
 * Gerenciador de Providers.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Providers
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gerencia todos os providers do plugin.
 */
class SMM_Providers_Manager {

	/**
	 * Array de providers carregados.
	 *
	 * @var array
	 */
	private static $providers = array();

	/**
	 * Registra um novo provider.
	 *
	 * @param string $slug   Slug do provider.
	 * @param string $class  Classe do provider.
	 * @return void
	 */
	public static function register_provider( $slug, $class ) {
		if ( class_exists( $class ) ) {
			self::$providers[ $slug ] = $class;
		}
	}

	/**
	 * Obtém um provider por slug.
	 *
	 * @param string $slug Slug do provider.
	 * @return SMM_Provider_Interface|null
	 */
	public static function get_provider( $slug ) {
		if ( ! isset( self::$providers[ $slug ] ) ) {
			return null;
		}

		return new self::$providers[ $slug ]();
	}

	/**
	 * Obtém um provider do banco de dados.
	 *
	 * @param string $slug Slug do provider.
	 * @return SMM_Provider_Interface|null
	 */
	public static function get_provider_from_db( $slug ) {
		global $wpdb;

		$provider_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}smm_providers WHERE provider_slug = %s",
				$slug
			)
		);

		if ( ! $provider_data ) {
			return null;
		}

		if ( ! isset( self::$providers[ $slug ] ) ) {
			return null;
		}

		$class = self::$providers[ $slug ];
		$configuration = json_decode( $provider_data->configuration, true );

		return new $class( $provider_data->id, $configuration );
	}

	/**
	 * Lista todos os providers registrados.
	 *
	 * @return array
	 */
	public static function get_providers() {
		return self::$providers;
	}

	/**
	 * Lista todos os providers do banco de dados.
	 *
	 * @return array
	 */
	public static function get_providers_from_db() {
		global $wpdb;

		$providers = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}smm_providers WHERE is_active = 1"
		);

		return $providers ? $providers : array();
	}

	/**
	 * Salva um provider no banco de dados.
	 *
	 * @param string $slug          Slug do provider.
	 * @param string $name          Nome do provider.
	 * @param string $type          Tipo do provider.
	 * @param array  $configuration Configuração.
	 * @return int|false
	 */
	public static function save_provider( $slug, $name, $type, $configuration ) {
		global $wpdb;

		// Verificar se já existe.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}smm_providers WHERE provider_slug = %s",
				$slug
			)
		);

		if ( $existing ) {
			// Atualizar.
			return $wpdb->update(
				$wpdb->prefix . 'smm_providers',
				array(
					'provider_name' => $name,
					'provider_type' => $type,
					'configuration' => wp_json_encode( $configuration ),
				),
				array( 'provider_slug' => $slug ),
				array( '%s', '%s', '%s' ),
				array( '%s' )
			) ? $existing : false;
		}

		// Inserir novo.
		return $wpdb->insert(
			$wpdb->prefix . 'smm_providers',
			array(
				'provider_slug'  => $slug,
				'provider_name'  => $name,
				'provider_type'  => $type,
				'configuration'  => wp_json_encode( $configuration ),
				'is_active'      => 1,
			),
			array( '%s', '%s', '%s', '%s', '%d' )
		) ? $wpdb->insert_id : false;
	}

	/**
	 * Ativa/desativa um provider.
	 *
	 * @param string $slug   Slug do provider.
	 * @param bool   $active Status.
	 * @return bool
	 */
	public static function toggle_provider( $slug, $active = true ) {
		global $wpdb;

		return (bool) $wpdb->update(
			$wpdb->prefix . 'smm_providers',
			array( 'is_active' => $active ? 1 : 0 ),
			array( 'provider_slug' => $slug ),
			array( '%d' ),
			array( '%s' )
		);
	}
}
