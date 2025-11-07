<?php

/**
 * Cepta – Woo Blocks support
 * - Registers Blocks JS, exposes gateway data, and surfaces failure messages.
 * - Preserves original behavior; adds safety checks and cleaner loading.
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

defined('ABSPATH') || exit;

final class WC_Gateway_Cepta_Blocks_Support extends AbstractPaymentMethodType
{

	/** @var string Payment method slug/id. */
	protected $name = 'cepta';

	/** @inheritDoc */
	public function initialize()
	{
		// Load the gateway settings row (used by get_setting()).
		$this->settings = get_option('woocommerce_cepta_settings', array());

		// Pipe PHP-side payment errors into the Store API result for this method.
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			array($this, 'failed_payment_notice'),
			8,
			2
		);
	}

	/** @inheritDoc */
	public function is_active()
	{
		$gateways = WC()->payment_gateways();
		if (! $gateways || empty($gateways->payment_gateways()['cepta'])) {
			return false;
		}
		$gateway = $gateways->payment_gateways()['cepta'];
		return ($gateway && is_callable(array($gateway, 'is_available'))) ? (bool) $gateway->is_available() : false;
	}

	/** @inheritDoc */
	public function get_payment_method_script_handles()
	{
		// Resolve asset metadata (deps/version) from generated asset file if present.
		$asset_file = trailingslashit(plugin_dir_path(WC_CEPTA_MAIN_FILE)) . 'assets/js/blocks/frontend/blocks.asset.php';
		$asset      = file_exists($asset_file)
			? require $asset_file
			: array('dependencies' => array(), 'version' => defined('WC_CEPTA_VERSION') ? WC_CEPTA_VERSION : '1.0.0');

		$script_url = plugins_url('/assets/js/blocks/frontend/blocks.js', WC_CEPTA_MAIN_FILE);

		wp_register_script(
			'wc-cepta-blocks',
			$script_url,
			is_array($asset['dependencies'] ?? null) ? $asset['dependencies'] : array(),
			$asset['version'] ?? null,
			true
		);

		if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations('wc-cepta-blocks', 'woo-cepta');
		}

		return array('wc-cepta-blocks');
	}

	/** @inheritDoc */
	public function get_payment_method_data()
	{
		$gateways = WC()->payment_gateways();
		$gateway  = $gateways && ! empty($gateways->payment_gateways()['cepta']) ? $gateways->payment_gateways()['cepta'] : null;

		// Fallbacks to avoid notices if gateway is not fully bootstrapped yet.
		$title       = $this->get_setting('title');
		$description = $this->get_setting('description');
		$supports    = array();
		$allow_saved = false;
		$logo_url    = '';

		if ($gateway) {
			// WC_Gateway_*::supports( $feature ) is used as predicate over the supports list.
			if (is_array($gateway->supports) && is_callable(array($gateway, 'supports'))) {
				$supports = array_values(array_filter($gateway->supports, array($gateway, 'supports')));
			}
			$allow_saved = ! empty($gateway->saved_cards) && is_user_logged_in();

			if (is_callable(array($gateway, 'get_logo_url'))) {
				$logo_url = (string) $gateway->get_logo_url();
			}
		}

		return array(
			'title'             => is_string($title) ? $title : '',
			'description'       => is_string($description) ? $description : '',
			'supports'          => $supports,
			'allow_saved_cards' => (bool) $allow_saved,
			'logo_url'          => $logo_url ? array($logo_url) : array(),
		);
	}

	/**
	 * Inject a payment failure message into the Store API response for Cepta.
	 *
	 * @param PaymentContext $context Payment context.
	 * @param PaymentResult  $result  Mutable payment result object.
	 * @return void
	 */
	public function failed_payment_notice(PaymentContext $context, PaymentResult &$result)
	{
		if ('cepta' !== $context->payment_method) {
			return;
		}

		add_action(
			'wc_gateway_cepta_process_payment_error',
			static function ($failed_notice) use (&$result) {
				$details                   = $result->payment_details;
				$details['errorMessage']   = wp_strip_all_tags((string) $failed_notice);
				$result->set_payment_details($details);
			}
		);
	}
}
