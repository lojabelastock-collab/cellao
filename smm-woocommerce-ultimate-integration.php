<?php
/**
 * Plugin Name: SMM WooCommerce Ultimate Integration
 * Plugin URI: https://github.com/lojabelastock-collab/smm-woocommerce-ultimate-integration
 * Description: Integração completa com redes sociais e plataformas SMM para WooCommerce
 * Version: 1.0.0
 * Author: Loja Belá Stock
 * Author URI: https://lojabelastock.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: smm-woocommerce-ultimate-integration
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 *
 * @package SMM_WooCommerce_Ultimate_Integration
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constantes do plugin.
if ( ! defined( 'SMM_PLUGIN_FILE' ) ) {
	define( 'SMM_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SMM_PLUGIN_DIR' ) ) {
	define( 'SMM_PLUGIN_DIR', plugin_dir_path( SMM_PLUGIN_FILE ) );
}

if ( ! defined( 'SMM_PLUGIN_URL' ) ) {
	define( 'SMM_PLUGIN_URL', plugin_dir_url( SMM_PLUGIN_FILE ) );
}

if ( ! defined( 'SMM_PLUGIN_BASENAME' ) ) {
	define( 'SMM_PLUGIN_BASENAME', plugin_basename( SMM_PLUGIN_FILE ) );
}

if ( ! defined( 'SMM_PLUGIN_VERSION' ) ) {
	define( 'SMM_PLUGIN_VERSION', '1.0.0' );
}

if ( ! defined( 'SMM_DB_VERSION' ) ) {
	define( 'SMM_DB_VERSION', '1.0.0' );
}

// Verificações de segurança e dependências.
require_once SMM_PLUGIN_DIR . 'core/class-smm-security-check.php';

// Verificar se o WooCommerce está ativo.
if ( ! SMM_Security_Check::is_woocommerce_active() ) {
	add_action( 'admin_notices', 'smm_woocommerce_inactive_notice' );
	return;
}

/**
 * Exibe notificação quando WooCommerce não está ativo.
 *
 * @return void
 */
function smm_woocommerce_inactive_notice() {
	if ( current_user_can( 'manage_options' ) ) {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'SMM WooCommerce Ultimate Integration requer WooCommerce ativo para funcionar.', 'smm-woocommerce-ultimate-integration' ); ?></p>
		</div>
		<?php
	}
}

// Carregar autoloader.
require_once SMM_PLUGIN_DIR . 'classes/class-smm-autoloader.php';

// Inicializar o autoloader.
SMM_Autoloader::init();

// Carregar o Core do plugin.
require_once SMM_PLUGIN_DIR . 'core/class-smm-core.php';

// Ativar hooks de instalação/desativação.
register_activation_hook( SMM_PLUGIN_FILE, array( 'SMM_Installer', 'activate' ) );
register_deactivation_hook( SMM_PLUGIN_FILE, array( 'SMM_Installer', 'deactivate' ) );

// Inicializar o plugin quando WordPress está carregado.
add_action( 'wp_loaded', array( 'SMM_Core', 'get_instance' ) );