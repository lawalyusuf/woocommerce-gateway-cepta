<?php

/**
 * Plugin Name: Cepta Payment Gateway for WooCommerce
 * Plugin URI:  https://cepta.co/
 * Description: Cepta WooCommerce Payment Gateway enables secure card and account transfer payments with real-time verification.
 * Version:     1.0.0
 * Author:      Cepta
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Requires Plugins: woocommerce
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * WC requires at least: 8.0.0
 * WC tested up to: 10.3.3
 * Text Domain: woo-cepta
 * Domain Path: /languages
 *
 * @package Woo_Cepta
 */

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;

defined('ABSPATH') || exit;

define('WC_CEPTA_MAIN_FILE', __FILE__);
define('WC_CEPTA_URL', untrailingslashit(plugins_url('/', __FILE__)));
define('WC_CEPTA_VERSION', '1.0.0');

/**
 * Bootstrap Cepta gateway.
 */
function wc_cepta_init()
{
	load_plugin_textdomain('woo-cepta', false, plugin_basename(dirname(__FILE__)) . '/languages');

	if (! class_exists('WC_Payment_Gateway')) {
		add_action('admin_notices', 'wc_cepta_wc_missing_notice');
		return;
	}

	add_action('admin_init', 'wc_cepta_testmode_notice');

	require_once __DIR__ . '/includes/class-wc-gateway-cepta.php';
	require_once __DIR__ . '/includes/class-wc-gateway-cepta-subscriptions.php';
	require_once __DIR__ . '/includes/class-wc-gateway-custom-cepta.php';

	add_filter('woocommerce_payment_gateways', 'wc_cepta_register_gateways', 99);
	add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_cepta_plugin_action_links');
}
add_action('plugins_loaded', 'wc_cepta_init', 99);

/**
 * Add Settings link in plugin list.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function wc_cepta_plugin_action_links($links)
{
	$settings = array(
		'settings' => sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=cepta')),
			esc_attr__('View Cepta WooCommerce Settings', 'woo-cepta'),
			esc_html__('Settings', 'woo-cepta')
		),
	);
	return array_merge($settings, $links);
}

/**
 * Register Cepta gateways.
 *
 * @param array $methods Existing payment gateways.
 * @return array
 */
function wc_cepta_register_gateways($methods)
{
	if (class_exists('WC_Subscriptions_Order') && class_exists('WC_Payment_Gateway_CC')) {
		$methods[] = 'WC_Gateway_Cepta_Subscriptions';
	} else {
		$methods[] = 'WC_Gateway_Cepta';
	}

	if ('NGN' === get_woocommerce_currency()) {
		$settings        = get_option('woocommerce_cepta_settings', array());
		$custom_gateways = $settings['custom_gateways'] ?? '';

		$map = array(
			'1' => array('WC_Gateway_Cepta_One'),
			'2' => array('WC_Gateway_Cepta_One', 'WC_Gateway_Cepta_Two'),
			'3' => array('WC_Gateway_Cepta_One', 'WC_Gateway_Cepta_Two', 'WC_Gateway_Cepta_Three'),
			'4' => array('WC_Gateway_Cepta_One', 'WC_Gateway_Cepta_Two', 'WC_Gateway_Cepta_Three', 'WC_Gateway_Cepta_Four'),
			'5' => array('WC_Gateway_Cepta_One', 'WC_Gateway_Cepta_Two', 'WC_Gateway_Cepta_Three', 'WC_Gateway_Cepta_Four', 'WC_Gateway_Cepta_Five'),
		);

		if (isset($map[$custom_gateways])) {
			$methods = array_merge($methods, $map[$custom_gateways]);
		}
	}

	return $methods;
}

/**
 * Admin notice when WooCommerce is missing.
 */
function wc_cepta_wc_missing_notice()
{
	printf(
		'<div class="error"><p><strong>%s</strong></p></div>',
		wp_kses(
			sprintf(
				/* translators: %s link to WooCommerce install page. */
				__('Cepta Payment Gateway requires WooCommerce to be installed and active. Click %s to install WooCommerce.', 'woo-cepta'),
				'<a href="' . esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=772&height=539')) . '" class="thickbox open-plugin-details-modal">' . esc_html__('here', 'woo-cepta') . '</a>'
			),
			array(
				'a'      => array(
					'href'  => array(),
					'class' => array(),
				),
				'strong' => array(),
			)
		)
	);
}

/**
 * Display WooCommerce Admin note when Cepta test mode is active.
 */
function wc_cepta_testmode_notice()
{
	if (! class_exists(Notes::class) || ! class_exists(WC_Data_Store::class) || ! method_exists(Notes::class, 'get_note_by_name')) {
		return;
	}

	if (Notes::get_note_by_name('cepta-test-mode')) {
		return;
	}

	$settings  = get_option('woocommerce_cepta_settings', array());
	$test_mode = $settings['testmode'] ?? '';

	if ('yes' !== $test_mode) {
		Notes::delete_notes_with_name('cepta-test-mode');
		return;
	}

	$note = new Note();
	$note->set_title(__('Cepta test mode enabled', 'woo-cepta'));
	$note->set_content(__('Cepta test mode is currently enabled. Remember to disable it when you are ready to accept live payments.', 'woo-cepta'));
	$note->set_type(Note::E_WC_ADMIN_NOTE_INFORMATIONAL);
	$note->set_layout('plain');
	$note->set_is_snoozable(false);
	$note->set_name('cepta-test-mode');
	$note->set_source('woo-cepta');
	$note->add_action(
		'disable-cepta-test-mode',
		__('Disable Cepta test mode', 'woo-cepta'),
		admin_url('admin.php?page=wc-settings&tab=checkout&section=cepta')
	);
	$note->save();
}

/**
 * Declare compatibility with WooCommerce features.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		}
	}
);

/**
 * Register WooCommerce Blocks support.
 */
function wc_cepta_register_block_support()
{
	if (class_exists(\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class)) {
		require_once __DIR__ . '/includes/class-wc-gateway-cepta-blocks-support.php';

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry) {
				$registry->register(new WC_Gateway_Cepta_Blocks_Support());
			}
		);
	}
}
add_action('woocommerce_blocks_loaded', 'wc_cepta_register_block_support');
