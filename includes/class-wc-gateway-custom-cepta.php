<?php

/**
 * CeptaPay – Custom Gateway (admin UI + checkout params)
 * - Settings, icons, param building, split filters, metadata, and checkout localization.
 */

defined('ABSPATH') || exit;

/**
 * Class WC_Gateway_Custom_Cepta
 *
 * Extends subscriptions
 */
class WC_Gateway_Custom_Cepta extends WC_Gateway_Cepta_Subscriptions
{

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __('Enable/Disable', 'woo-cepta'),
				/* translators: payment method title */
				'label'       => sprintf(__('Enable cepta - %s', 'woo-cepta'), $this->title),
				'type'        => 'checkbox',
				'description' => __('Enable this gateway as a payment option on the checkout page.', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'title' => array(
				'title'       => __('Title', 'woo-cepta'),
				'type'        => 'text',
				'description' => __('This controls the payment method title which the user sees during checkout.', 'cepta-wc'),
				'desc_tip'    => true,
				'default'     => __('cepta', 'cepta-wc'),
			),
			'description' => array(
				'title'       => __('Description', 'woo-cepta'),
				'type'        => 'textarea',
				'description' => __('This controls the payment method description which the user sees during checkout.', 'woo-cepta'),
				'desc_tip'    => true,
				'default'     => '',
			),
			'payment_page' => array(
				'title'       => __('Payment Option', 'woo-cepta'),
				'type'        => 'select',
				'description' => __('Popup shows the payment popup on the page while Redirect will redirect the customer to Cepta to make payment.', 'cepta-wc'),
				'default'     => '',
				'desc_tip'    => false,
				'options'     => array(
					''         => __('Select One', 'cepta-wc'),
					'inline'   => __('Popup', 'woo-cepta'),
					'redirect' => __('Redirect', 'woo-cepta'),
				),
			),
			'autocomplete_order' => array(
				'title'       => __('Autocomplete Order After Payment', 'woo-cepta'),
				'label'       => __('Autocomplete Order', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-autocomplete-order',
				'description' => __('If enabled, the order will be marked as complete after successful payment', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'remove_cancel_order_button' => array(
				'title'       => __('Remove Cancel Order & Restore Cart Button', 'woo-cepta'),
				'label'       => __('Remove the cancel order & restore cart button on the pay for order page', 'woo-cepta'),
				'type'        => 'checkbox',
				'default'     => 'no',
			),
			'subaccount_code' => array(
				'title'       => __('Subaccount Code', 'woo-cepta'),
				'type'        => 'text',
				'description' => __('Enter the subaccount code here.', 'woo-cepta'),
				'class'       => 'woocommerce_cepta_subaccount_code',
				'default'     => '',
			),
			'payment_channels' => array(
				'title'             => __('Payment Channels', 'woo-cepta'),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select wc-cepta-payment-channels',
				'description'       => __('The payment channels enabled for this gateway', 'woo-cepta'),
				'default'           => array(),
				'desc_tip'          => true,
				'select_buttons'    => true,
				'options'           => $this->channels(),
				'custom_attributes' => array(
					'data-placeholder' => __('Select payment channels', 'woo-cepta'),
				),
			),
			'cards_allowed' => array(
				'title'             => __('Allowed Card Brands', 'woo-cepta'),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select wc-cepta-cards-allowed',
				'description'       => __('The card brands allowed for this gateway. This filter only works with the card payment channel.', 'woo-cepta'),
				'default'           => array(),
				'desc_tip'          => true,
				'select_buttons'    => true,
				'options'           => $this->card_types(),
				'custom_attributes' => array(
					'data-placeholder' => __('Select card brands', 'woo-cepta'),
				),
			),
			'banks_allowed' => array(
				'title'             => __('Allowed Banks Card', 'woo-cepta'),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select wc-cepta-banks-allowed',
				'description'       => __('The banks whose card should be allowed for this gateway. This filter only works with the card payment channel.', 'woo-cepta'),
				'default'           => array(),
				'desc_tip'          => true,
				'select_buttons'    => true,
				'options'           => $this->banks(),
				'custom_attributes' => array(
					'data-placeholder' => __('Select banks', 'woo-cepta'),
				),
			),
			'payment_icons' => array(
				'title'             => __('Payment Icons', 'woo-cepta'),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select wc-cepta-payment-icons',
				'description'       => __('The payment icons to be displayed on the checkout page.', 'woo-cepta'),
				'default'           => array(),
				'desc_tip'          => true,
				'select_buttons'    => true,
				'options'           => $this->payment_icons(),
				'custom_attributes' => array(
					'data-placeholder' => __('Select payment icons', 'woo-cepta'),
				),
			),
			'custom_metadata' => array(
				'title'       => __('Custom Metadata', 'woo-cepta'),
				'label'       => __('Enable Custom Metadata', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-metadata',
				'description' => __('If enabled, you will be able to send more information about the order to cepta.', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_order_id' => array(
				'title'       => __('Order ID', 'woo-cepta'),
				'label'       => __('Send Order ID', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-order-id',
				'description' => __('If checked, the Order ID will be sent to cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_name' => array(
				'title'       => __('Customer Name', 'woo-cepta'),
				'label'       => __('Send Customer Name', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-name',
				'description' => __('If checked, the customer full name will be sent to Cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_email' => array(
				'title'       => __('Customer Email', 'woo-cepta'),
				'label'       => __('Send Customer Email', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-email',
				'description' => __('If checked, the customer email address will be sent to Cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_phone' => array(
				'title'       => __('Customer Phone', 'woo-cepta'),
				'label'       => __('Send Customer Phone', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-phone',
				'description' => __('If checked, the customer phone will be sent to cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_billing_address' => array(
				'title'       => __('Order Billing Address', 'woo-cepta'),
				'label'       => __('Send Order Billing Address', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-billing-address',
				'description' => __('If checked, the order billing address will be sent to Cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_shipping_address' => array(
				'title'       => __('Order Shipping Address', 'woo-cepta'),
				'label'       => __('Send Order Shipping Address', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-shipping-address',
				'description' => __('If checked, the order shipping address will be sent to Cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_products' => array(
				'title'       => __('Product(s) Purchased', 'woo-cepta'),
				'label'       => __('Send Product(s) Purchased', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-products',
				'description' => __('If checked, the product(s) purchased will be sent to Cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Admin Panel Options.
	 */
	public function admin_options()
	{
		$cepta_settings_url   = admin_url('admin.php?page=wc-settings&tab=checkout&section=cepta');
		$checkout_settings_url = admin_url('admin.php?page=wc-settings&tab=checkout');
?>
		<h2>
			<?php
			printf(
				/* translators: %s payment method title */
				wp_kses_post(__('Cepta - %s', 'woo-cepta')),
				esc_html($this->title)
			);
			?>
			<?php if (function_exists('wc_back_link')) : ?>
				<?php wc_back_link(__('Return to payments', 'woo-cepta'), $checkout_settings_url); ?>
			<?php endif; ?>
		</h2>

		<h4>
			<?php
			printf(
				/* translators: %s is the link to set the webhook URL */
				wp_kses_post(__('Important: To avoid situations where bad network makes it impossible to verify transactions, set your webhook URL <a href="%s" target="_blank" rel="noopener noreferrer">here</a> to the URL below', 'cepta-woocommerce')),
				esc_url('#')
			);
			?>
		</h4>

		<p style="color:red">
			<code><?php echo esc_url(WC()->api_request_url('cepta-wc_webhook')); ?></code>
		</p>

		<p>
			<?php
			printf(
				/* translators: %s is the link to the Cepta settings page */
				wp_kses_post(__('To configure your Cepta Authentication Token and enable/disable test mode, do that <a href="%s">here</a>', 'cepta-woocommerce')),
				esc_url($cepta_settings_url)
			);
			?>
		</p>

<?php
		if ($this->is_valid_for_use()) {
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		} else {
			echo '<div class="inline error"><p><strong>' .
				wp_kses(
					sprintf(
						/* translators: %s is an error message */
						__('Cepta Payment Gateway Disabled: %s', 'cepta-woocommerce'),
						esc_html($this->msg)
					),
					array('strong' => array())
				) .
				'</strong></p></div>';
		}
	}

	/**
	 * Payment Channels.
	 */
	public function channels()
	{
		return array(
			'card'          => __('Cards', 'woo-cepta'),
			'bank'          => __('Pay with Bank', 'woo-cepta'),
			'ussd'          => __('USSD', 'woo-cepta'),
			'qr'            => __('QR', 'woo-cepta'),
			'bank_transfer' => __('Bank Transfer', 'woo-cepta'),
		);
	}

	/**
	 * Card Types.
	 */
	public function card_types()
	{
		return array(
			'visa'       => __('Visa', 'woo-cepta'),
			'verve'      => __('Verve', 'woo-cepta'),
			'mastercard' => __('Mastercard', 'woo-cepta'),
		);
	}

	/**
	 * Banks.
	 */
	public function banks()
	{
		return array(
			'044'  => __('Access Bank', 'woo-cepta'),
			'035A' => __('ALAT by WEMA', 'woo-cepta'),
			'401'  => __('ASO Savings and Loans', 'woo-cepta'),
			'023'  => __('Citibank Nigeria', 'woo-cepta'),
			'063'  => __('Access Bank (Diamond)', 'woo-cepta'),
			'050'  => __('Ecobank Nigeria', 'woo-cepta'),
			'562'  => __('Ekondo Microfinance Bank', 'woo-cepta'),
			'084'  => __('Enterprise Bank', 'woo-cepta'),
			'070'  => __('Fidelity Bank', 'woo-cepta'),
			'011'  => __('First Bank of Nigeria', 'woo-cepta'),
			'214'  => __('First City Monument Bank', 'woo-cepta'),
			'058'  => __('Guaranty Trust Bank', 'woo-cepta'),
			'030'  => __('Heritage Bank', 'woo-hcepta'),
			'301'  => __('Jaiz Bank', 'woo-cepta'),
			'082'  => __('Keystone Bank', 'woo-cepta'),
			'014'  => __('MainStreet Bank', 'woo-cepta'),
			'526'  => __('Parallex Bank', 'woo-cepta'),
			'076'  => __('Polaris Bank Limited', 'woo-cepta'),
			'101'  => __('Providus Bank', 'woo-cepta'),
			'221'  => __('Stanbic IBTC Bank', 'woo-cepta'),
			'068'  => __('Standard Chartered Bank', 'woo-cepta'),
			'232'  => __('Sterling Bank', 'woo-cepta'),
			'100'  => __('Suntrust Bank', 'woo-cepta'),
			'032'  => __('Union Bank of Nigeria', 'woo-cepta'),
			'033'  => __('United Bank For Africa', 'woo-cepta'),
			'215'  => __('Unity Bank', 'woo-cepta'),
			'035'  => __('Wema Bank', 'woo-cepta'),
			'057'  => __('Zenith Bank', 'woo-cepta'),
		);
	}

	/**
	 * Payment Icons.
	 */
	public function payment_icons()
	{
		return array(
			'verve'      => __('Verve', 'woo-cepta'),
			'visa'       => __('Visa', 'woo-cepta'),
			'mastercard' => __('Mastercard', 'woo-cepta'),
			'ceptawhite' => __('Secured by Cepta White', 'woo-cepta'),
			'ceptablue'  => __('Secured by Cepta Blue', 'woo-cepta'),
			'cepta-wc'   => __('Cepta Nigeria', 'woo-cepta'),
			'cepta-gh'   => __('Cepta Ghana', 'woo-cepta'),
			'access'     => __('Access Bank', 'woo-cepta'),
			'alat'       => __('ALAT by WEMA', 'woo-cepta'),
			'aso'        => __('ASO Savings and Loans', 'woo-cepta'),
			'citibank'   => __('Citibank Nigeria', 'woo-cepta'),
			'diamond'    => __('Access Bank (Diamond)', 'woo-cepta'),
			'ecobank'    => __('Ecobank Nigeria', 'woo-cepta'),
			'ekondo'     => __('Ekondo Microfinance Bank', 'woo-cepta'),
			'enterprise' => __('Enterprise Bank', 'woo-cepta'),
			'fidelity'   => __('Fidelity Bank', 'woo-cepta'),
			'firstbank'  => __('First Bank of Nigeria', 'woo-cepta'),
			'fcmb'       => __('First City Monument Bank', 'woo-cepta'),
			'gtbank'     => __('Guaranty Trust Bank', 'woo-cepta'),
			'heritage'   => __('Heritage Bank', 'woo-cepta'),
			'jaiz'       => __('Jaiz Bank', 'woo-cepta'),
			'keystone'   => __('Keystone Bank', 'woo-cepta'),
			'mainstreet' => __('MainStreet Bank', 'woo-cepta'),
			'parallex'   => __('Parallex Bank', 'woo-cepta'),
			'polaris'    => __('Polaris Bank Limited', 'woo-cepta'),
			'providus'   => __('Providus Bank', 'woo-cepta'),
			'stanbic'    => __('Stanbic IBTC Bank', 'woo-cepta'),
			'standard'   => __('Standard Chartered Bank', 'woo-cepta'),
			'sterling'   => __('Sterling Bank', 'woo-cepta'),
			'suntrust'   => __('Suntrust Bank', 'woo-cepta'),
			'union'      => __('Union Bank of Nigeria', 'woo-cepta'),
			'uba'        => __('United Bank For Africa', 'woo-cepta'),
			'unity'      => __('Unity Bank', 'woo-cepta'),
			'wema'       => __('Wema Bank', 'woo-cepta'),
			'zenith'     => __('Zenith Bank', 'woo-cepta'),
		);
	}

	/**
	 * Display the selected payment icon(s).
	 */
	public function get_icon()
	{
		$base_img   = trailingslashit(WC_CEPTA_URL) . 'assets/images/';
		$icon_html  = '<img src="' . esc_url(WC_HTTPS::force_https_url($base_img . 'cepta.png')) . '" alt="' . esc_attr__('cepta', 'woo-cepta') . '" style="height:40px;margin-right:.4em;margin-bottom:.6em;" />';

		$icons = is_array($this->payment_icons) ? $this->payment_icons : array();
		$more  = '';

		foreach ($icons as $i) {
			$img = $base_img . sanitize_file_name($i) . '.png';
			$alt = preg_replace('/[^a-z0-9_\- ]/i', '', $i);
			$more .= '<img src="' . esc_url(WC_HTTPS::force_https_url($img)) . '" alt="' . esc_attr($alt) . '" style="height:40px;margin-right:.4em;margin-bottom:.6em;" />';
		}

		$icon_html .= $more;
		return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
	}

	/**
	 * Outputs scripts used for Cepta payment (localizes checkout params).
	 * Assumes the actual JS (wc_cepta) is registered/enqueued elsewhere.
	 */
	public function payment_scripts()
	{
		// Only on order pay page & for this gateway.
		if (isset($_GET['pay_for_order']) || ! is_checkout_pay_page()) {
			return;
		}
		if ('no' === $this->enabled) {
			return;
		}

		// Nonce validation (passed via payment redirect).
		if (! isset($_GET['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'wc_cepta_payment_nonce')) {
			wp_die(
				esc_html__('Invalid request. Nonce verification failed.', 'text-domain'),
				esc_html__('Error', 'text-domain'),
				array('response' => 403)
			);
		}

		$order_key = '';
		if (isset($_GET['key'])) {
			$order_key = rawurldecode(sanitize_text_field(wp_unslash($_GET['key'])));
		}

		$order_id = absint(get_query_var('order-pay'));
		$order    = wc_get_order($order_id);

		if (! $order || $this->id !== $order->get_payment_method()) {
			return;
		}

		// Make sure jQuery exists for any dependent scripts.
		wp_enqueue_script('jquery');

		$cepta_params = array(
			'key' => $this->public_key, // from parent
		);

		if (is_checkout_pay_page() && get_query_var('order-pay')) {

			$email         = $order->get_billing_email();
			$amount        = $order->get_total();
			$txnref        = $order_id . '_' . time();
			$the_order_id  = $order->get_id();
			$the_order_key = $order->get_order_key();
			$currency      = $order->get_currency();

			if ((int) $the_order_id === (int) $order_id && $the_order_key === $order_key) {
				$cepta_params['email']    = $email;
				$cepta_params['amount']   = $amount;
				$cepta_params['txnref']   = $txnref;
				$cepta_params['currency'] = $currency;
			}

			// Split payment options.
			if (! empty($this->split_payment)) {
				$cepta_params['subaccount_code']     = $this->subaccount_code;
				$cepta_params['charges_account']     = $this->charges_account;
				if (isset($this->transaction_charges) && '' !== $this->transaction_charges) {
					$cepta_params['transaction_charges'] = (float) $this->transaction_charges * 100;
				}
			}

			// Channels toggles.
			$channels = isset($this->payment_channels) && is_array($this->payment_channels) ? $this->payment_channels : array();
			if (in_array('bank', $channels, true)) {
				$cepta_params['bank_channel']          = 'true';
			}
			if (in_array('card', $channels, true)) {
				$cepta_params['card_channel']          = 'true';
			}
			if (in_array('ussd', $channels, true)) {
				$cepta_params['ussd_channel']          = 'true';
			}
			if (in_array('qr', $channels, true)) {
				$cepta_params['qr_channel']            = 'true';
			}
			if (in_array('bank_transfer', $channels, true)) {
				$cepta_params['bank_transfer_channel'] = 'true';
			}

			// Allow lists (banks/cards).
			if (isset($this->banks) && is_array($this->banks) && $this->banks) {
				$cepta_params['banks_allowed'] = array_map('sanitize_text_field', $this->banks);
			}
			if (isset($this->cards) && is_array($this->cards) && $this->cards) {
				$cepta_params['cards_allowed'] = array_map('sanitize_text_field', $this->cards);
			}

			// Optional metadata.
			if (! empty($this->custom_metadata)) {
				if (! empty($this->meta_order_id)) {
					$cepta_params['meta_order_id'] = $order_id;
				}
				if (! empty($this->meta_name)) {
					$cepta_params['meta_name'] = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
				}
				if (! empty($this->meta_email)) {
					$cepta_params['meta_email'] = $email;
				}
				if (! empty($this->meta_phone)) {
					$cepta_params['meta_phone'] = $order->get_billing_phone();
				}
				if (! empty($this->meta_products)) {
					$line_items = $order->get_items();
					$products   = array();
					foreach ($line_items as $item) {
						$name     = isset($item['name']) ? $item['name'] : '';
						$quantity = isset($item['qty']) ? (int) $item['qty'] : 0;
						if ($name) {
							$products[] = sprintf('%s (Qty: %d)', $name, $quantity);
						}
					}
					if ($products) {
						$cepta_params['meta_products'] = implode(' | ', $products);
					}
				}
				if (! empty($this->meta_billing_address)) {
					$billing = esc_html(preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_billing_address()));
					$cepta_params['meta_billing_address'] = $billing;
				}
				if (! empty($this->meta_shipping_address)) {
					$shipping = esc_html(preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_shipping_address()));
					if (empty($shipping)) {
						$shipping = esc_html(preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_billing_address()));
					}
					$cepta_params['meta_shipping_address'] = $shipping;
				}
			}

			$order->update_meta_data('_cepta_txn_ref', $txnref);
			$order->save();
		}

		// Provide params to your frontend script (must be registered/enqueued elsewhere).
		wp_localize_script('wc_cepta', 'wc_cepta_params', $cepta_params);
	}

	/**
	 * Add/remove this gateway from checkout list at runtime.
	 *
	 * @param array $available_gateways
	 * @return array
	 */
	public function add_gateway_to_checkout($available_gateways)
	{
		if ('no' === $this->enabled) {
			unset($available_gateways[$this->id]);
		}
		return $available_gateways;
	}
}
