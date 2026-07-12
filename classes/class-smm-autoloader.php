<?php
/**
 * Autoloader PSR-4 para o plugin SMM WooCommerce Ultimate Integration.
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @subpackage Classes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe responsável pelo carregamento automático de classes.
 */
class SMM_Autoloader {

	/**
	 * Mapa de namespaces e diretórios.
	 *
	 * @var array
	 */
	private static $namespace_map = array(
		'SMM'                      => 'classes',
		'SMM_Admin'                => 'admin',
		'SMM_API'                  => 'api',
		'SMM_Background'           => 'background',
		'SMM_Core'                 => 'core',
		'SMM_Frontend'             => 'frontend',
		'SMM_Importer'             => 'importer',
		'SMM_Modules'              => 'modules',
		'SMM_Providers'            => 'providers',
		'SMM_Widgets'              => 'widgets',
		'SMM_Helpers'              => 'includes/helpers',
		'SMM_Traits'               => 'includes/traits',
	);

	/**
	 * Inicializa o autoloader.
	 *
	 * @return void
	 */
	public static function init() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Carrega a classe dinamicamente.
	 *
	 * @param string $class_name Nome completo da classe.
	 * @return void
	 */
	public static function autoload( $class_name ) {
		// Verifica se a classe começa com SMM_.
		if ( 0 !== strpos( $class_name, 'SMM' ) ) {
			return;
		}

		// Extrai o namespace raiz e a parte restante.
		$parts = explode( '\\', $class_name );
		if ( empty( $parts ) ) {
			return;
		}

		// Determina o diretório base.
		$directory = self::get_directory( $class_name );

		if ( ! $directory ) {
			return;
		}

		// Converte o nome da classe em caminho de arquivo.
		$file_path = self::get_file_path( $class_name, $directory );

		// Carrega o arquivo se existir.
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}

	/**
	 * Obtém o diretório base para a classe.
	 *
	 * @param string $class_name Nome da classe.
	 * @return string|null
	 */
	private static function get_directory( $class_name ) {
		foreach ( self::$namespace_map as $namespace => $directory ) {
			if ( 0 === strpos( $class_name, $namespace ) ) {
				return $directory;
			}
		}
		return null;
	}

	/**
	 * Obtém o caminho do arquivo da classe.
	 *
	 * @param string $class_name Nome da classe.
	 * @param string $directory Diretório base.
	 * @return string
	 */
	private static function get_file_path( $class_name, $directory ) {
		// Substitui backslashes por barras.
		$class_parts = str_replace( '\\', '/', $class_name );

		// Remove o prefixo SMM_.
		$class_parts = preg_replace( '/^SMM_/', '', $class_parts );
		$class_parts = preg_replace( '/^SMM\//', '', $class_parts );

		// Converte em snake_case para o arquivo.
		$class_parts = strtolower( $class_parts );
		$class_parts = preg_replace( '/([a-z])([A-Z])/', '$1_$2', $class_parts );
		$class_parts = strtolower( $class_parts );

		// Monta o caminho final.
		$file_path = SMM_PLUGIN_DIR . $directory . '/class-' . str_replace( '/', '-', $class_parts ) . '.php';

		return $file_path;
	}

	/**
	 * Registra namespaces adicionais dinamicamente.
	 *
	 * @param string $namespace Namespace.
	 * @param string $directory Diretório.
	 * @return void
	 */
	public static function register_namespace( $namespace, $directory ) {
		self::$namespace_map[ $namespace ] = $directory;
	}
}
