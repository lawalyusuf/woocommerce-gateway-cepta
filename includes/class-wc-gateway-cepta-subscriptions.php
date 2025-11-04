<?php

/**
 * CeptaPay – Subscriptions bridge
 * - Keeps original behavior for free-trial (₦0) orders and scheduled renewals.
 * - Hooks WCS renewal action and defers non-trial flows to parent gateway.
 */

defined('ABSPATH') || exit;

/**
 * Class WC_Gateway_Cepta_Subscriptions
 */
class WC_Gateway_Cepta_Subscriptions extends WC_Gateway_Cepta
{

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		// Register renewal hook only if WooCommerce Subscriptions is available.
		if (class_exists('WC_Subscriptions_Order') || class_exists('WC_Subscriptions')) {
			add_action(
				'woocommerce_scheduled_subscription_payment_' . $this->id,
				array($this, 'scheduled_subscription_payment'),
				10,
				2
			);
		}
	}

	/**
	 * Process payment.
	 * If the order contains a subscription and total is zero (free trial), complete immediately.
	 * Otherwise, delegate to parent::process_payment (keeps original flow).
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return array|void
	 */
	public function process_payment($order_id)
	{
		$order = wc_get_order($order_id);
		if (! $order) {
			return;
		}

		// Use parent helper; treat zero-total subscription trials as immediate success.
		if ($this->order_contains_subscription($order_id) && floatval($order->get_total()) == 0.0) {
			$order->payment_complete();
			$order->add_order_note(__('This subscription has a free trial, reason for the 0 amount', 'woo-cepta'));

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url($order),
			);
		}

		return parent::process_payment($order_id);
	}

	/**
	 * Handle scheduled subscription renewal payments (WCS cron).
	 * Calls $this->process_subscription_payment() and marks order failed on WP_Error.
	 *
	 * @param float    $amount_to_charge Amount to capture.
	 * @param WC_Order $renewal_order    Renewal order object.
	 * @return void
	 */
	public function scheduled_subscription_payment($amount_to_charge, $renewal_order)
	{
		$response = $this->process_subscription_payment($renewal_order, $amount_to_charge);

		if (is_wp_error($response)) {
			/* translators: %s error message from gateway */
			$renewal_order->update_status(
				'failed',
				sprintf(__('Cepta Transaction Failed (%s)', 'woo-cepta'), $response->get_error_message())
			);
		}
	}
}
