<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class WC_Gateway_Cepta_Subscriptions
 */
class WC_Gateway_Cepta_Subscriptions extends WC_Gateway_Cepta
{

	/**
	 * Constructor
	 */
	public function __construct()
	{

		parent::__construct();

		if (class_exists('WC_Subscriptions_Order')) {

			add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
		}
	}

	/**
	 * Process a trial subscription order with 0 total.
	 *
	 * @param int $order_id WC Order ID.
	 *
	 * @return array|void
	 */
	public function process_payment($order_id)
	{

		$order = wc_get_order($order_id);

		// Check for trial subscription order with 0 total.
		if ($this->order_contains_subscription($order) && $order->get_total() == 0) {

			$order->payment_complete();

			$order->add_order_note(__('This subscription has a free trial, reason for the 0 amount', 'woo-cepta'));

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url($order),
			);
		} else {

			return parent::process_payment($order_id);
		}
	}

	/**
	 * Process a subscription renewal.
	 *
	 * @param float    $amount_to_charge Subscription payment amount.
	 * @param WC_Order $renewal_order Renewal Order.
	 */
	public function scheduled_subscription_payment($amount_to_charge, $renewal_order)
	{

		$response = $this->process_subscription_payment($renewal_order, $amount_to_charge);

		if (is_wp_error($response)) {
			// Translators: %s is the error message returned by the payment gateway.
			$renewal_order->update_status('failed', sprintf(__('Cepta Transaction Failed (%s)', 'woo-cepta'), $response->get_error_message()));
		}
	}
}
