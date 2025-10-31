<?php

/**
 * Plugin Name: Cepta Payment Gateway for WooCommerce
 * Plugin URI: https://cepta.co/
 * Description: Cepta WooCommerce Payment Gateway provides secure, seamless card and account transfer processing for quick and efficient transactions.
 * Version: 1.0.0
 * Author: Cepta
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires Plugins: woocommerce
 * Requires at least: 6.2
 * Requires PHP: 7.4+
 * WC requires at least: 8.0.0
 * WC tested up to: 10.3.3
 * Text Domain: woocommerce-gateway-cepta
 * Domain Path: /languages
 */

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;

if (!defined('ABSPATH')) {
	exit;
}

define('WC_CEPTA_MAIN_FILE', __FILE__);
define('WC_CEPTA_URL', untrailingslashit(plugins_url('/', __FILE__)));

define('WC_CEPTA_VERSION', '1.0.0');

/**
 * Initialize Cepta payment gateway for wooCommerce.
 */
function cep_wc_cepta_init()
{

	load_plugin_textdomain('woo-cepta', false, plugin_basename(dirname(__FILE__)) . '/languages');

	if (!class_exists('WC_Payment_Gateway')) {
		add_action('admin_notices', 'cep_wc_cepta_wc_missing_notice');
		return;
	}

	add_action('admin_init', 'cep_wc_cepta_testmode_notice');

	require_once dirname(__FILE__) . '/includes/class-wc-gateway-cepta.php';

	require_once dirname(__FILE__) . '/includes/class-wc-gateway-cepta-subscriptions.php';

	require_once dirname(__FILE__) . '/includes/class-wc-gateway-custom-cepta.php';

	add_filter('woocommerce_payment_gateways', 'cep_wc_add_cepta_gateway', 99);

	add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cep_woo_cepta_plugin_action_links');
}
add_action('plugins_loaded', 'cep_wc_cepta_init', 99);

/**
 * Add Settings link to the plugin entry in the plugins menu.
 *
 * @param array $links Plugin action links.
 *
 * @return array
 **/
function cep_woo_cepta_plugin_action_links($links)
{

	$settings_link = array(
		'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=cepta') . '" title="' . __('View Cepta WooCommerce Settings', 'cepta-wc') . '">' . __('Settings', 'cepta-wc') . '</a>',
	);

	return array_merge($settings_link, $links);
}

/**
 * Add Cepta Gateway for WooCommerce.
 *
 * @param array $methods for wooCommerce payment gateways methods.
 *
 * @return array
 */
function cep_wc_add_cepta_gateway($methods)
{

	if (class_exists('WC_Subscriptions_Order') && class_exists('WC_Payment_Gateway_CC')) {
		$methods[] = 'WC_Gateway_Cepta_Subscriptions';
	} else {
		$methods[] = 'WC_Gateway_Cepta';
	}

	if ('NGN' === get_woocommerce_currency()) {

		$settings        = get_option('woocommerce_cepta_settings', '');
		$custom_gateways = isset($settings['custom_gateways']) ? $settings['custom_gateways'] : '';

		switch ($custom_gateways) {
			case '5':
				$methods[] = 'WC_Gateway_Cepta_One';
				$methods[] = 'WC_Gateway_Cepta_Two';
				$methods[] = 'WC_Gateway_Cepta_Three';
				$methods[] = 'WC_Gateway_Cepta_Four';
				$methods[] = 'WC_Gateway_Cepta_Five';
				break;

			case '4':
				$methods[] = 'WC_Gateway_Cepta_One';
				$methods[] = 'WC_Gateway_Cepta_Two';
				$methods[] = 'WC_Gateway_Cepta_Three';
				$methods[] = 'WC_Gateway_Cepta_Four';
				break;

			case '3':
				$methods[] = 'WC_Gateway_Cepta_One';
				$methods[] = 'WC_Gateway_Cepta_Two';
				$methods[] = 'WC_Gateway_Cepta_Three';
				break;

			case '2':
				$methods[] = 'WC_Gateway_Cepta_One';
				$methods[] = 'WC_Gateway_Cepta_Two';
				break;

			case '1':
				$methods[] = 'WC_Gateway_Cepta_One';
				break;

			default:
				break;
		}
	}

	return $methods;
}

/**
 * Display a notice if WooCommerce is not installed
 */

function cep_wc_cepta_wc_missing_notice()
{

	echo '<div class="error"><p><strong>' . wp_kses(
		sprintf(
			// Translators: %s is a link to install WooCommerce
			__('Cepta Payment Gateway requires WooCommerce to be installed and active. Click %s to install WooCommerce.', 'woo-cepta'),
			'<a href="' . esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=772&height=539')) . '" class="thickbox open-plugin-details-modal">' . esc_html__('here', 'woo-cepta') . '</a>'
		),
		[
			'a' => [
				'href' => [],
				'class' => [],
			],
			'strong' => [],
		]
	) . '</strong></p></div>';
}

/**
 * Display the test mode notice.
 **/
function cep_wc_cepta_testmode_notice()
{

	if (!class_exists(Notes::class)) {
		return;
	}

	if (!class_exists(WC_Data_Store::class)) {
		return;
	}

	if (!method_exists(Notes::class, 'get_note_by_name')) {
		return;
	}

	$test_mode_note = Notes::get_note_by_name('cepta-test-mode');

	if (false !== $test_mode_note) {
		return;
	}

	$cepta_settings = get_option('woocommerce_cepta_settings');
	$test_mode         = $cepta_settings['testmode'] ?? '';

	if ('yes' !== $test_mode) {
		Notes::delete_notes_with_name('cepta-test-mode');

		return;
	}

	$note = new Note();
	$note->set_title(__('Cepta test mode enabled', 'woo-cepta'));
	$note->set_content(__('Cepta test mode is currently enabled. Remember to disable it when you want to start accepting live payment on your site.', 'woo-cepta'));
	$note->set_type(Note::E_WC_ADMIN_NOTE_INFORMATIONAL);
	$note->set_layout('plain');
	$note->set_is_snoozable(false);
	$note->set_name('cepta-test-mode');
	$note->set_source('woo-cepta');
	$note->add_action('disable-cepta-test-mode', __('Disable Cepta test mode', 'woo-cepta'), admin_url('admin.php?page=wc-settings&tab=checkout&section=cepta'));
	$note->save();
}

add_action(
	'before_woocommerce_init',
	function () {
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		}
	}
);

/**
 * Registers WooCommerce Blocks integration.
 */
function cep_wc_gateway_cepta_woocommerce_block_support()
{
	if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
		require_once __DIR__ . '/includes/class-wc-gateway-cepta-blocks-support.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
				$payment_method_registry->register(new WC_Gateway_Cepta_Blocks_Support());
			}
		);
	}
}
add_action('woocommerce_blocks_loaded', 'cep_wc_gateway_cepta_woocommerce_block_support');
