<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_Gateway_Cepta extends WC_Payment_Gateway_CC
{

	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Should orders be marked as complete after payment?
	 * 
	 * @var bool
	 */
	public $autocomplete_order;

	/**
	 * Cepta payment page type.
	 *
	 * @var string
	 */
	public $payment_page;

	/**
	 * Cepta test public key.
	 *
	 * @var string
	 */
	public $test_public_key;

	/**
	 * Cepta test secret key.
	 *
	 * @var string
	 */
	public $test_secret_key;

	/**
	 * Cepta live public key.
	 *
	 * @var string
	 */
	public $live_public_key;

	/**
	 * Cepta live secret key.
	 *
	 * @var string
	 */
	public $live_secret_key;

	/**
	 * Should we save customer cards?
	 *
	 * @var bool
	 */
	public $saved_cards;

	/**
	 * Should Cepta split payment be enabled.
	 *
	 * @var bool
	 */
	public $split_payment;

	/**
	 * Should the cancel & remove order button be removed on the pay for order page.
	 *
	 * @var bool
	 */
	public $remove_cancel_order_button;

	/**
	 * Cepta sub account code.
	 *
	 * @var string
	 */
	public $subaccount_code;

	/**
	 * Who bears Cepta charges?
	 *
	 * @var string
	 */
	public $charges_account;

	/**
	 * A flat fee to charge the sub account for each transaction.
	 *
	 * @var string
	 */
	public $transaction_charges;

	/**
	 * Should custom metadata be enabled?
	 *
	 * @var bool
	 */
	public $custom_metadata;

	/**
	 * Should the order id be sent as a custom metadata to Cepta?
	 *
	 * @var bool
	 */
	public $meta_order_id;

	/**
	 * Should the customer name be sent as a custom metadata to Cepta?
	 *
	 * @var bool
	 */
	public $meta_name;

	/**
	 * Should the billing email be sent as a custom metadata to Cepta?
	 *
	 * @var bool
	 */
	public $meta_email;

	/**
	 * Should the billing phone be sent as a custom metadata to Cepta?
	 *
	 * @var bool
	 */
	public $meta_phone;

	/**
	 * Should the billing address be sent as a custom metadata to Cepta?
	 *
	 * @var bool
	 */
	public $meta_billing_address;

	/**
	 * Should the shipping address be sent as a custom metadata to Cepta?
	 *
	 * @var bool
	 */
	public $meta_shipping_address;

	/**
	 * Should the order items be sent as a custom metadata to Cepta?
	 *
	 * @var bool
	 */
	public $meta_products;

	/**
	 * API public key
	 *
	 * @var string
	 */
	public $public_key;

	/**
	 * API secret key
	 *
	 * @var string
	 */
	public $secret_key;

	/**
	 * Gateway disabled message
	 *
	 * @var string
	 */
	public $msg;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->id                 = 'cepta';
		$this->method_title       = __('Cepta Payment Gateway', 'woo-cepta');
		// Translators: %1$s is the URL for signing up, %2$s is the URL for obtaining the authentication token.
		$this->method_description = sprintf(__('Cepta Payment Gateway helps you process payments using cards and account transfers for faster delivery of goods and services.. <a href="%1$s" target="_blank">Sign up</a> for a Cepta account, and <a href="%2$s" target="_blank">get your authentication token</a>.', 'cepta-woocommerce'), 'https://app.cepta.co/home/', 'https://app.cepta.co/home');
		$this->has_fields         = true;

		$this->payment_page = $this->get_option('payment_page');

		$this->supports = array(
			'products',
			'refunds',
			'tokenization',
			'subscriptions',
			'multiple_subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
		);

		// Load the form fields
		$this->init_form_fields();

		// Load the settings
		$this->init_settings();

		// Get setting values

		$this->title              = $this->get_option('title');
		$this->description        = $this->get_option('description');
		$this->enabled            = $this->get_option('enabled');
		$this->testmode           = $this->get_option('testmode') === 'yes' ? true : false;
		$this->autocomplete_order = $this->get_option('autocomplete_order') === 'yes' ? true : false;

		$this->test_public_key = $this->get_option('test_public_key');
		$this->test_secret_key = $this->get_option('test_secret_key');

		$this->live_public_key = $this->get_option('live_public_key');
		$this->live_secret_key = $this->get_option('live_secret_key');

		$this->saved_cards = $this->get_option('saved_cards') === 'yes' ? true : false;

		$this->split_payment              = $this->get_option('split_payment') === 'yes' ? true : false;
		$this->remove_cancel_order_button = $this->get_option('remove_cancel_order_button') === 'yes' ? true : false;
		$this->subaccount_code            = $this->get_option('subaccount_code');
		$this->charges_account            = $this->get_option('split_payment_charge_account');
		$this->transaction_charges        = $this->get_option('split_payment_transaction_charge');

		$this->custom_metadata = $this->get_option('custom_metadata') === 'yes' ? true : false;

		$this->meta_order_id         = $this->get_option('meta_order_id') === 'yes' ? true : false;
		$this->meta_name             = $this->get_option('meta_name') === 'yes' ? true : false;
		$this->meta_email            = $this->get_option('meta_email') === 'yes' ? true : false;
		$this->meta_phone            = $this->get_option('meta_phone') === 'yes' ? true : false;
		$this->meta_billing_address  = $this->get_option('meta_billing_address') === 'yes' ? true : false;
		$this->meta_shipping_address = $this->get_option('meta_shipping_address') === 'yes' ? true : false;
		$this->meta_products         = $this->get_option('meta_products') === 'yes' ? true : false;

		$this->public_key = $this->testmode ? $this->test_public_key : $this->live_public_key;
		$this->secret_key = $this->testmode ? $this->test_secret_key : $this->live_secret_key;

		// Hooks
		add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

		add_action('admin_notices', array($this, 'admin_notices'));
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);

		add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

		// Webhook listener/API hook.
		add_action('woocommerce_api_tbz_wc_cepta_webhook', array($this, 'process_webhooks'));

		add_action('woocommerce_api_cepta_wc_payment', array($this, 'cepta_wc_payment_popup_action'));

		// Cepta Payment confirmation listener/API hook  .
		add_action('woocommerce_api_wc_gateway_cepta', array($this, 'verify_cepta_wc_transaction'));

		//Cepta Popup confirmation test
		add_action('woocommerce_api_wc_gateway_cepta_popup', array($this, 'verify_cepta_wc_transaction_popup'));

		// Check if the gateway can be used.
		if (!$this->is_valid_for_use()) {
			$this->enabled = false;
		}
	}

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 */
	public function is_valid_for_use()
	{

		if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_cepta_supported_currencies', array('NGN', 'USD', 'ZAR', 'GHS', 'KES', 'XOF', 'EGP')))) {
			// Translators: %s is the URL to the WooCommerce general settings page.
			$this->msg = sprintf(__('Cepta does not support your store currency. Kindly set it to either NGN (&#8358), GHS (&#x20b5;), USD (&#36;), KES (KSh), ZAR (R), XOF (CFA), or EGP (E£) <a href="%s">here</a>', 'woo-cepta'), admin_url('admin.php?page=wc-settings&tab=general'));

			return false;
		}

		return true;
	}

	/**
	 * Display Cepta payment icon.
	 */
	public function get_icon()
	{

		$base_location = wc_get_base_location();

		if ('GH' === $base_location['country']) {
			$icon = '<img src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-gh.png', WC_CEPTA_MAIN_FILE)) . '" alt="Cepta Payment Options" />';
		} elseif ('ZA' === $base_location['country']) {
			$icon = '<img src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-za.png', WC_CEPTA_MAIN_FILE)) . '" alt="Cepta Payment Options" />';
		} elseif ('KE' === $base_location['country']) {
			$icon = '<img src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-ke.png', WC_CEPTA_MAIN_FILE)) . '" alt="Cepta Payment Options" />';
		} else {
			$icon = '<img src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-wc.png', WC_CEPTA_MAIN_FILE)) . '" alt="Cepta Payment Options" />';
		}

		return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
	}


	/**
	 * Check if Cepta merchant details is filled.
	 */
	public function admin_notices()
	{

		if ($this->enabled == 'no') {
			return;
		}

		// Check required fields & URL is properly escaped, preventing any potential security risks.
		if (!($this->public_key || $this->secret_key)) {
			// Translators: %1$s is the HTML link to the WooCommerce settings page where users can enter their Cepta merchant details.
			echo '<div class="error"><p>' . wp_kses_post(sprintf(
				// Translators: %s: link to WooCommerce settings page
				esc_html__('Please enter your Cepta merchant details %s to be able to use the Cepta WooCommerce plugin.', 'woo-cepta'),
				'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=cepta')) . '">' . esc_html__('here', 'woo-cepta') . '</a>'
			)) . '</p></div>';
			return;
		}
	}

	/**
	 * Check if Cepta gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_available()
	{

		if ('yes' == $this->enabled) {

			if (!($this->secret_key)) {


				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Admin Panel Options.
	 */
	public function admin_options()
	{

?>

		<h2><?php esc_html_e('Cepta Payment Gateway', 'woo-cepta'); ?></h2>
		<h2>
			<?php
			if (function_exists('wc_back_link')) {
				// Translators: 'Return to payments' is a link back to the WooCommerce payments settings.
				wc_back_link(esc_html__('Return to payments', 'woo-cepta'), esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')));
			}
			?>
		</h2>

		<h4>


			<?php
			printf(
				// Translators: %1$s is the link to set the webhook URL, %2$s is the webhook URL itself.
				wp_kses_post(__('Optional: To avoid situations where bad network makes it impossible to verify transactions, set your webhook URL <a href="%1$s" target="_blank" rel="noopener noreferrer">here</a> to the URL below<span style="color: red"><pre><code>%2$s</code></pre></span>', 'cepta-wc')),
				esc_url('#'),
				esc_html(WC()->api_request_url('cepta-wc_webhook'))
			);
			?>

		</h4>

		<?php

		if ($this->is_valid_for_use()) {

			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		} else {
		?>
			<div class="inline error">
				<p><strong><?php esc_html_e('Cepta Payment Gateway Disabled', 'woo-cepta'); ?></strong>: <?php echo esc_html($this->msg); ?></p>
			</div>

<?php
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields()
	{

		$form_fields = array(
			'enabled'                          => array(
				'title'       => __('Enable/Disable', 'woo-cepta'),
				'label'       => __('Enable cepta', 'woo-cepta'),
				'type'        => 'checkbox',
				'description' => __('Enable Cepta as a payment option on the checkout page.', 'cepta-woocommerce-payment'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'title'                            => array(
				'title'       => __('Title', 'cepta-woocommerce-payment'),
				'type'        => 'text',
				'description' => __('This controls the payment method title which the user sees during checkout.', 'cepta-woocommerce-payment'),
				'default'     => __('CeptaPay Payment Gateway', 'cepta-woocommerce-payment'),
				'desc_tip'    => true,
			),
			'description'                      => array(
				'title'       => __('Description', 'cepta-wc'),
				'type'        => 'textarea',
				'description' => __('This controls the payment method description which the user sees during checkout.', 'cepta-wc'),
				'default'     => __('Powered by Cepta. Accepts Mastercard, Visa, Verve.', 'cepta-wc'),
				'desc_tip'    => true,
			),
			'testmode'                         => array(
				'title'       => __('Test mode', 'woo-cepta'),
				'label'       => __('Enable Test Mode', 'woo-cepta'),
				'type'        => 'checkbox',
				'description' => __('Test mode enables you to test payments before going live. <br />Once the LIVE MODE is enabled on your Cepta account uncheck this.', 'woo-cepta'),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'payment_page'                     => array(
				'title'       => __('Payment Option', 'cepta-wc'),
				'type'        => 'select',
				'description' => __('Inline Checkout lets customers pay directly on your site, while Redirect takes them to Cepta to complete payment.', 'woo-cepta'),
				'default'     => '',
				'desc_tip'    => false,
				'options'     => array(
					''          => __('Select One', 'woo-cepta'),
					'inline'    => __('Inline Checkout', 'woo-cepta'),
					'redirect'  => __('Redirect', 'woo-cepta'),
				),
			),
			'test_secret_key'                  => array(
				'title'       => __('Sandbox API Key', 'woo-cepta'),
				'type'        => 'password',
				'description' => __('Enter your Sandbox API Key here', 'woo-cepta'),
				'default'     => '',
			),
			'test_public_key'                  => array(
				'title'       => __('Test Public Key', 'woo-cepta'),
				'type'        => 'password',
				'description' => __('Enter your Test Public Key here', 'woo-cepta'),
				'default'     => '',
			),

			'live_secret_key'                  => array(
				'title'       => __('Live API Key', 'woo-cepta'),
				'type'        => 'password',
				'description' => __('Enter your Live Api key here.', 'woo-cepta'),
				'default'     => '',
			),

			'live_public_key'                  => array(
				'title'       => __('Live Public Key', 'woo-cepta'),
				'type'        => 'password',
				'description' => __('Enter your Live Public Key here', 'woo-cepta'),
				'default'     => '',
			),
			'autocomplete_order'               => array(
				'title'       => __('Autocomplete Order After Payment', 'woo-cepta'),
				'label'       => __('Autocomplete Order', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-autocomplete-order',
				'description' => __('If enabled, the order will be marked as complete after successful payment', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'remove_cancel_order_button'       => array(
				'title'       => __('Remove Cancel Order & Restore Cart Button', 'woo-cepta'),
				'label'       => __('Remove the cancel order & restore cart button on the pay for order page', 'woo-cepta'),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),

			'saved_cards'                      => array(
				'title'       => __('Saved Cards', 'woo-cepta'),
				'label'       => __('Enable Payment via Saved Cards', 'woo-cepta'),
				'type'        => 'checkbox',
				'description' => __('If enabled, users will be able to pay with a saved card during checkout. Card details are saved on Cepta servers, not on your store.<br>Note that you need to have a valid SSL certificate installed.', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'custom_metadata'                  => array(
				'title'       => __('Custom Metadata', 'woo-cepta'),
				'label'       => __('Enable Custom Metadata', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-metadata',
				'description' => __('If enabled, you will be able to send more information about the order to Cepta.', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_order_id'                    => array(
				'title'       => __('Order ID', 'woo-cepta'),
				'label'       => __('Send Order ID', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-order-id',
				'description' => __('If checked, the Order ID will be sent to Cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_name'                        => array(
				'title'       => __('Customer Name', 'woo-cepta'),
				'label'       => __('Send Customer Name', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-name',
				'description' => __('If checked, the customer full name will be sent to Cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_email'                       => array(
				'title'       => __('Customer Email', 'woo-cepta'),
				'label'       => __('Send Customer Email', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-email',
				'description' => __('If checked, the customer email address will be sent to cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_phone'                       => array(
				'title'       => __('Customer Phone', 'woo-cepta'),
				'label'       => __('Send Customer Phone', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-phone',
				'description' => __('If checked, the customer phone will be sent to cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_billing_address'             => array(
				'title'       => __('Order Billing Address', 'woo-cepta'),
				'label'       => __('Send Order Billing Address', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-billing-address',
				'description' => __('If checked, the order billing address will be sent to cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_shipping_address'            => array(
				'title'       => __('Order Shipping Address', 'woo-cepta'),
				'label'       => __('Send Order Shipping Address', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-shipping-address',
				'description' => __('If checked, the order shipping address will be sent to Cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'meta_products'                    => array(
				'title'       => __('Product(s) Purchased', 'woo-cepta'),
				'label'       => __('Send Product(s) Purchased', 'woo-cepta'),
				'type'        => 'checkbox',
				'class'       => 'wc-cepta-meta-products',
				'description' => __('If checked, the product(s) purchased will be sent to Cepta', 'woo-cepta'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
		);

		if ('NGN' !== get_woocommerce_currency()) {
			unset($form_fields['custom_gateways']);
		}

		$this->form_fields = $form_fields;
	}

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields()
	{

		if ($this->description) {
			echo wp_kses_post(wpautop(wptexturize($this->description)));
		}

		if (!is_ssl()) {
			return;
		}

		// Add nonce field for security
		wp_nonce_field('wc_cepta_payment_nonce_action', 'wc_cepta_payment_nonce');

		if ($this->supports('tokenization') && is_checkout() && $this->saved_cards && is_user_logged_in()) {
			$this->tokenization_script();
			$this->saved_payment_methods();
			$this->save_payment_method_checkbox();
		}
	}

	/**
	 * Outputs scripts used for cepta payment.
	 */

	public function payment_scripts()
	{

		if (isset($_GET['pay_for_order']) || !is_checkout_pay_page()) {
			return;
		}

		if ($this->enabled === 'no') {
			return;
		}

		// Verify the nonce before processing further
		if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'wc_cepta_payment_nonce')) {
			wp_die(
				esc_html__('Invalid request. Nonce verification failed.', 'text-domain'),
				esc_html__('Error', 'text-domain'),
				array('response' => 403)
			);
		}

		if (isset($_GET['key'])) {
			$order_key = sanitize_text_field(wp_unslash($_GET['key']));
		}

		$order_id  = absint(get_query_var('order-pay'));

		$order = wc_get_order($order_id);

		if ($this->id !== $order->get_payment_method()) {
			return;
		}

		$script_src = $this->testmode ?
			'https://lawalyusuf.github.io/ceptest/cep.js' :
			'https://lawalyusuf.github.io/ceptest/cep.js';

		$public_key = $this->testmode ? $this->test_public_key : $this->live_public_key;
		$secret_key = $this->testmode ? $this->test_secret_key : $this->live_secret_key;
		$base_url   = 'https://dev-adapter.cepta.co'; // Assuming consistent dev/live base URL

		$suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

		wp_enqueue_script('jquery');

		wp_enqueue_script('cepta', $script_src, array('jquery'), WC_CEPTA_VERSION, false);

		wp_enqueue_script('wc_cepta', plugins_url('assets/js/cepta' . $suffix . '.js', WC_CEPTA_MAIN_FILE), array('jquery', 'cepta'), WC_CEPTA_VERSION, false);


		$cepta_params = array(
			'public_key' => $public_key,
			'secret_key' => $secret_key,
			'base_url'   => $base_url,
			'key' => $secret_key,
			'nonce' => wp_create_nonce('wc_cepta_payment_nonce'), // Generate and pass nonce
		);

		if (is_checkout_pay_page() && get_query_var('order-pay')) {

			$email         = $order->get_billing_email();
			$amount        = $order->get_total();
			$txnref        = $order_id . '_' . time();
			$the_order_id  = $order->get_id();
			$the_order_key = $order->get_order_key();
			$currency      = $order->get_currency();

			if ($the_order_id == $order_id && $the_order_key == $order_key) {

				$cepta_params['email']    = $email;
				$cepta_params['amount']   = $amount;
				$cepta_params['txnref']   = $txnref;
				$cepta_params['currency'] = $currency;

				// Get the "My Account" URL
				$cepta_wc_redirect_url = wc_get_page_permalink('myaccount');

				// Pass the "My Account" URL to your JavaScript
				$cepta_params['cepta_wc_redirect_url'] = $cepta_wc_redirect_url;
			}

			if ($this->split_payment) {

				$cepta_params['subaccount_code'] = $this->subaccount_code;
				$cepta_params['charges_account'] = $this->charges_account;

				if (empty($this->transaction_charges)) {
					$cepta_params['transaction_charges'] = '';
				} else {
					$cepta_params['transaction_charges'] = $this->transaction_charges * 100;
				}
			}

			if ($this->custom_metadata) {

				if ($this->meta_order_id) {

					$cepta_params['meta_order_id'] = $order_id;
				}

				if ($this->meta_name) {

					$cepta_params['meta_name'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
				}

				if ($this->meta_email) {

					$cepta_params['meta_email'] = $email;
				}

				if ($this->meta_phone) {

					$cepta_params['meta_phone'] = $order->get_billing_phone();
				}

				if ($this->meta_products) {

					$line_items = $order->get_items();

					$products = '';

					foreach ($line_items as $item_id => $item) {
						$name      = $item['name'];
						$quantity  = $item['qty'];
						$products .= $name . ' (Qty: ' . $quantity . ')';
						$products .= ' | ';
					}

					$products = rtrim($products, ' | ');

					$cepta_params['meta_products'] = $products;
				}

				if ($this->meta_billing_address) {

					$billing_address = $order->get_formatted_billing_address();
					$billing_address = esc_html(preg_replace('#<br\s*/?>#i', ', ', $billing_address));

					$cepta_params['meta_billing_address'] = $billing_address;
				}

				if ($this->meta_shipping_address) {

					$shipping_address = $order->get_formatted_shipping_address();
					$shipping_address = esc_html(preg_replace('#<br\s*/?>#i', ', ', $shipping_address));

					if (empty($shipping_address)) {

						$billing_address = $order->get_formatted_billing_address();
						$billing_address = esc_html(preg_replace('#<br\s*/?>#i', ', ', $billing_address));

						$shipping_address = $billing_address;
					}

					$cepta_params['meta_shipping_address'] = $shipping_address;
				}
			}

			$order->update_meta_data('_cepta_txn_ref', $txnref);
			$order->save();
		}

		wp_localize_script('wc_cepta', 'wc_cepta_params', $cepta_params);
	}


	/**
	 * Load admin scripts.
	 */
	public function admin_scripts()
	{

		if ('woocommerce_page_wc-settings' !== get_current_screen()->id) {
			return;
		}

		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

		$cepta_admin_params = array(
			'plugin_url' => WC_CEPTA_URL,
		);

		wp_enqueue_script('wc_cepta_admin', plugins_url('assets/js/cepta-admin' . $suffix . '.js', WC_CEPTA_MAIN_FILE), array(), WC_CEPTA_VERSION, true);

		wp_localize_script('wc_cepta_admin', 'wc_cepta_admin_params', $cepta_admin_params);
	}

	/**
	 * Process the payment for Cepta.
	 *
	 * @param int $order_id
	 *
	 * @return array|void
	 */
	public function process_payment($order_id)
	{

		// Verify nonce before processing
		if (
			!isset($_POST['wc_cepta_payment_nonce']) ||
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wc_cepta_payment_nonce'])), 'wc_cepta_payment_nonce_action')
		) {
			wc_add_notice(__('Security check failed, please try again.', 'woocommerce'), 'error');
			return;
		}

		if ('redirect' === $this->payment_page) {

			// For the 'redirect' payment method, initiate the redirect payment option,n.,

			return $this->process_redirect_payment_option($order_id);
		} elseif (isset($_POST['wc-' . $this->id . '-payment-token']) && 'new' !== $_POST['wc-' . $this->id . '-payment-token']) {

			// Cepta Payment with token
			$token_id = sanitize_text_field(wp_unslash($_POST['wc-' . $this->id . '-payment-token']));
			$token    = \WC_Payment_Tokens::get($token_id);

			if ($token->get_user_id() !== get_current_user_id()) {

				wc_add_notice('Invalid token ID', 'error');

				return;
			} else {

				$status = $this->process_token_payment($token->get_token(), $order_id);

				if ($status) {

					$order = wc_get_order($order_id);

					// Generate nonce key
					$nonce = wp_create_nonce('wc_cepta_payment_nonce');

					// Save nonce in session or order meta
					WC()->session->set('wc_cepta_payment_nonce', $nonce);
					$order->update_meta_data('_wc_cepta_payment_nonce', $nonce);
					$order->save();

					// Redirect URL with nonce key
					$redirect_url = add_query_arg(array(
						'nonce' => $nonce,
					), $this->get_return_url($order));

					return array(
						'result'   => 'success',
						'redirect' => $redirect_url,
					);
				}
			}
		} else {

			// Handle other payment scenarios for Cepta

			$order = wc_get_order($order_id);

			if (is_user_logged_in() && isset($_POST['wc-' . $this->id . '-new-payment-method']) && true === (bool) $_POST['wc-' . $this->id . '-new-payment-method'] && $this->saved_cards) {

				$order->update_meta_data('_wc_cepta_save_card', true);

				$order->save();
			}

			// Generate nonce key
			$nonce = wp_create_nonce('wc_cepta_payment_nonce');

			// Save nonce in session or order meta
			WC()->session->set('wc_cepta_payment_nonce', $nonce);
			$order->update_meta_data('_wc_cepta_payment_nonce', $nonce);
			$order->save();

			// Construct redirect URL with nonce key
			$redirect_url = add_query_arg(array(
				// 'order_id'  => $order_id,
				// 'key'       => $order->get_order_key(),
				'nonce' => $nonce,
			), $order->get_checkout_payment_url(true));

			return array(
				'result'   => 'success',
				'redirect' => $redirect_url,
			);
		}
	}



	/**
	 * Process a redirect payment option payment.
	 *
	 * @since 5.7
	 * @param int $order_id
	 * @return array|void
	 */

	/**
	 * Processes the payment and redirects to the Cepta payment page.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return array
	 */

	/**
	 * Processes the payment and redirects to the Cepta payment page.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return array|null
	 */
	public function process_redirect_payment_option($order_id)
	{
		// --- 1. Security & Setup ---
		// Verify nonce before processing
		if (
			!isset($_POST['wc_cepta_payment_nonce']) ||
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wc_cepta_payment_nonce'])), 'wc_cepta_payment_nonce_action')
		) {
			wc_add_notice(__('Security check failed, please try again.', 'woocommerce'), 'error');
			return;
		}

		$order = wc_get_order($order_id);

		// FIX 1: RESTORE CORRECT AMOUNT CONVERSION.
		// Amount MUST be multiplied by 100 to represent the smallest currency unit (e.g., Kobo/Cents).
		$amount_raw = floatval($order->get_total());
		// Correct calculation: e.g., 50.12 * 100 = 5012
		$amount = intval(round($amount_raw)); // Use round() for safety before intval

		// NEW VALIDATION: Check for minimum required amount (50 Naira = 5000 Kobo/Cents)

		$min_amount = 50;
		if ($amount < $min_amount) {
			wc_add_notice(
				__('Min amount cannot be less than 50 Naira. Please add more product.', 'woo-cepta'),
				'error'
			);
			return;
		}

		$txnref = 'CEP_' . $order_id . '_' . time();
		$ts = time();
		$path = '/api/v1/pay';
		$method = 'POST';
		$nonce = wp_create_nonce('wc_cepta_payment_nonce');

		// Determine environment keys and URL

		if ($this->testmode) {
			$public_key = $this->test_public_key;
			$secret_key = $this->test_secret_key;
			$cepta_url = 'https://dev-adapter.cepta.co/api/v1/pay';
		} else {
			$public_key = $this->live_public_key;
			$secret_key = $this->live_secret_key;
			$cepta_url = 'https://dev-adapter.cepta.co/api/v1/pay';
		}

		// Set the callback URL with the nonce for verification on return
		$callback_url = add_query_arg('nonce', $nonce, $order->get_checkout_payment_url(true));

		$cepta_params = array(
			'amount'             => $amount,
			'currency'           => $order->get_currency(),
			'description'        => 'Payment for order ID ' . $order->get_id(),
			'pageName'           => '',
			'transactionReference' => $txnref,
			'customerEmail'      => $order->get_billing_email(),
			'customUrlText'      => '',
			'callbackUrl'         => $callback_url, // Use the correct WooCommerce callback URL
			'isPlugin' => true,
		);

		// Optional payment channels
		$payment_channels = $this->get_gateway_payment_channels($order);
		if (!empty($payment_channels)) {
			$cepta_params['channels'] = $payment_channels;
		}

		// Generate the JSON body string ONCE with consistent flags for signature matching.
		$json_flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		$payload_body = wp_json_encode($cepta_params, $json_flags); // Uses the correct $cepta_params

		if (false === $payload_body) {
			wc_add_notice(__('Error: Could not encode payment parameters to JSON.', 'woo-cepta'), 'error');
			return;
		}

		// Create the signature string: timestamp + method + path + JSON Body
		$payload_string = $ts . $method . $path . $payload_body;
		$signature = hash_hmac('sha256', $payload_string, $secret_key);

		// Save transaction reference
		$order->update_meta_data('_cepta_txn_ref', $txnref);
		$order->save();

		// --- 4. Execute API Request ---

		// Headers, including signature
		$headers = array(
			'Accept'             => 'application/json',
			'Content-Type'       => 'application/json',
			'X-Access-Key'       => $public_key,
			'X-Access-Ts'        => $ts,
			'X-Access-Signature' => $signature,
			'Cache-Control'      => 'no-cache',
		);

		// Arguments for wp_remote_post
		$args = array(
			'headers'     => $headers,
			'timeout'     => 60,
			'body'        => $payload_body,
			'data_format' => 'body',
		);

		$request = wp_remote_post($cepta_url, $args);

		if (is_wp_error($request)) {
			wc_add_notice(
				sprintf(__('Unable to connect to Cepta payment gateway. Error: %s', 'woo-cepta'), $request->get_error_message()),
				'error'
			);
			return;
		}

		$response_code = wp_remote_retrieve_response_code($request);
		$response_body_raw = wp_remote_retrieve_body($request);
		$response_body = json_decode($response_body_raw);

		// Log detailed response for debugging
		// wc_add_notice('Debug: Request JSON: ' . esc_html($payload_body), 'notice');
		// wc_add_notice('Debug: Signature String: ' . esc_html($payload_string), 'notice');
		// wc_add_notice('Debug: Final Signature: ' . esc_html($signature), 'notice');
		// wc_add_notice('Debug: Cepta Raw Response (Code ' . $response_code . '): ' . esc_html($response_body_raw), 'notice');


		if (200 === $response_code && isset($response_body->data->paymentUrl)) {
			return array(
				'result' => 'success',
				'redirect' => $response_body->data->paymentUrl,
			);
		} else {
			// Payment initiation failed.
			$error_message = isset($response_body->message) ? $response_body->message : 'Unknown API error.';

			wc_add_notice(
				sprintf(__('Payment initiation failed. Error: %s (Code: %d)', 'woo-cepta'), $error_message, $response_code),
				'error'
			);
			return;
		}
	}

	/**
	 * Process a token payment.
	 *
	 * @param $token
	 * @param $order_id
	 *
	 * @return bool
	 */


	/**
	 * Show new card can only be added when placing an order notice.
	 */
	public function add_payment_method()
	{

		wc_add_notice(__('You can only add a new card when placing an order.', 'woo-cepta'), 'error');

		return;
	}

	/**
	 * Displays the payment page.
	 *
	 * @param $order_id
	 */
	public function receipt_page($order_id)
	{

		$order = wc_get_order($order_id);

		echo '<div id="wc-cepta-form">';

		echo '<p>' . esc_html__('Thank you for your order, please click the button below to pay with Cepta Payment Gateway.', 'cepta-wc') . '</p>';

		echo '<div id="cepta_form"><form id="order_review" method="post" action="' . esc_url(WC()->api_request_url('WC_Gateway_Cepta')) . '"></form><button class="button" id="cepta-payment-button">' . esc_html__('Pay Now', 'woo-cepta') . '</button>';

		if (!$this->remove_cancel_order_button) {
			echo '  <a class="button cancel" id="cepta-cancel-payment-button" href="' . esc_url($order->get_cancel_order_url()) . '">' . esc_html__('Cancel order &amp; restore cart', 'woo-cepta') . '</a></div>';
		}

		echo '</div>';
	}

	/**
	 * Verify Cepta Woo Payment Transaction.
	 */


	/**
	 * Verifies the transaction status using the CeptaPay /api/v1/pay/confirm-status endpoint
	 * with the required HMAC signature for the GET request.
	 * * NOTE: This function handles an AJAX call from the client side 
	 * after the payment modal is closed, using 'transactionRef' and 'ceptOderId'.
	 */

	/**
	 * Verifies a transaction using the transaction reference from the AJAX request.
	 *
	 * @return void Sends a JSON response and exits.
	 */
	function verify_cepta_wc_transaction_popup()
	{
		// Exit immediately if security checks fail
		if (
			!isset($_POST['wc_cepta_payment_nonce']) ||
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wc_cepta_payment_nonce'])), 'wc_cepta_payment_nonce')
		) {
			wp_send_json(array('result' => 'error', 'message' => __('Security check failed, please try again.', 'woocommerce')));
			return;
		}

		// Ensure required transaction data is present
		if (!isset($_POST['transactionRef']) || !isset($_POST['ceptaOderId'])) {
			wp_send_json(array('result' => 'error', 'message' => 'TransactionRef or OrderId not found in POST data.'));
			return;
		}

		$ceptaTransactionRef = sanitize_text_field(wp_unslash($_POST['transactionRef']));
		$ceptaOrderId        = sanitize_text_field(wp_unslash($_POST['ceptaOderId']));
		$order_id               = (int) $ceptaOrderId;
		$ocepta              = wc_get_order($order_id);

		$is_test_mode = $this->testmode ?? true; // Default to true if not defined
		$public_key   = $is_test_mode ? $this->test_public_key : $this->live_public_key;
		$secret_key   = $is_test_mode ? $this->test_secret_key : $this->live_secret_key;
		$base_url     = 'https://dev-adapter.cepta.co';
		$ts           = time();
		$method       = 'GET';
		$path         = '/api/v1/pay/confirm-status';
		$full_url     = $base_url . $path . '?TransactionRef=' . urlencode($ceptaTransactionRef);

		$payload_string = $ts . $method . $path;
		$signature      = hash_hmac('sha256', $payload_string, $secret_key);

		$headers = array(
			'Accept'             => 'application/json',
			'X-Access-Key'       => $public_key,
			'X-Access-Ts'        => $ts,
			'X-Access-Signature' => $signature,
			'Cache-Control'      => 'no-cache',
		);

		$args = array(
			'method'      => 'GET',
			'headers'     => $headers,
			'timeout'     => 60,
		);

		$response = wp_remote_get($full_url, $args);

		if (is_wp_error($response)) {
			wp_send_json(array('result' => 'error', 'message' => $response->get_error_message()));
			return;
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);
		$response_data = json_decode($response_body);
		$error_message = '';

		// --- TEMP DEBUG LOGGING (Check PHP Error Log) ---
		// error_log('Cepta API Raw Response: ' . $response_body);
		// error_log('Cepta API Decoded Data: ' . print_r($response_data, true));

		if (200 === $response_code && isset($response_data->data->status)) {

			$cepta_api_status = $response_data->data->status;
			$cepta_transaction_ref = $ceptaTransactionRef;
			if ('Successful' === $cepta_api_status) {

				// Prevent processing an already completed order
				if (in_array($order->get_status(), array('processing', 'completed', 'on-hold'))) {
					WC()->cart->empty_cart();
					wp_send_json(array('statusRes' => true, 'status' => 'success', 'message' => 'Order already processed.', 'redirect' => $order->get_checkout_order_received_url()));
					return;
				}

				$order_total      = $order->get_total();
				$order_currency   = $order->get_currency();
				$currency_symbol  = get_woocommerce_currency_symbol($order_currency);
				$amount_paid      = floatval($response_data->data->amount);
				$payment_currency = strtoupper($response_data->data->currency ?? $order_currency); // Use null coalescing for safety
				$gateway_symbol   = get_woocommerce_currency_symbol($payment_currency);
				$notice           = '';

				//  Amount Check
				if ($amount_paid < $order_total) {
					$order->update_status('on-hold', __('Amount paid is less than the total order amount.', 'woo-cepta'));
					$order->add_order_note(sprintf(__('Amount Paid was %1$s (%2$s) while total order amount is %3$s (%4$s)', 'woo-cepta'), $gateway_symbol, $amount_paid, $currency_symbol, $order_total));
					$notice = __('Your payment was successful, but the amount paid is less than the order total. Your order is on hold.', 'woo-cepta');

					// Currency Check
				} elseif ($payment_currency !== $order_currency) {
					$order->update_status('on-hold', __('Payment currency is different from the order currency.', 'woo-cepta'));
					$order->add_order_note(sprintf(__('Order Currency is %1$s while the payment currency is %2$s', 'woo-cepta'), $order_currency, $payment_currency));
					$notice = __('Your payment was successful, but the payment currency is different from the order currency. Your order is on hold.', 'woo-cepta');

					// Complete Success (Amount and Currency Match)
				} else {
					$order->payment_complete($cepta_transaction_ref);
					$order->add_order_note(sprintf(__('Payment via CeptaPay Successful (Transaction Reference: %s)', 'woo-cepta'), $cepta_transaction_ref));

					if ($this->is_autocomplete_order_enabled($order)) {
						$order->update_status('completed', '');
					}
					$notice = __('Thank you for shopping with us. Your payment transaction was successful.', 'woo-cepta');
				}

				// Final success actions
				$order->update_meta_data('_transaction_id', $cepta_transaction_ref);
				$order->save();
				$this->save_card_details($response_data, $order->get_user_id(), $order_id);
				WC()->cart->empty_cart();
				wc_add_notice($notice, 'notice');

				// Respond to AJAX call with success
				wp_send_json(array(
					'statusRes' => true,
					'status'    => 'success',
					'message'   => 'Payment verified.',
					'redirect'  => $this->get_return_url($order)
				));
			} elseif ('Failed' === $cepta_api_status) {
				// Transaction failed
				$error_message = $response_data->message ?? 'Payment declined by gateway.';
				$order->update_status("failed", sprintf(__('Payment was declined by CeptaPay. Details: %s', 'woo-cepta'), $error_message));
				wp_send_json(array('statusRes' => false, 'status' => 'error', 'message' => 'Payment failed or declined.'));
			} else {
				// Pending/Unknown status
				$order->add_order_note(sprintf(__('Payment status is currently: %s', 'woo-cepta'), $cepta_api_status));
				wp_send_json(array('statusRes' => false, 'status' => 'pending', 'message' => 'Payment status is still pending: ' . $cepta_api_status));
			}
		} else {
			// Handle API server error, non-200 code, or unexpected response structure
			$error_message = $response_data->message ?? 'API verification failed.';
			$order->update_status("failed", sprintf(__('Transaction verification failed. API Code %d. Error: %s', 'woo-cepta'), $response_code, $error_message));

			// Respond with failure and include debug info
			wp_send_json(array(
				'statusRes' => false,
				'status' => 'error',
				'message' => 'Verification failed: ' . $error_message,
				'debug_raw_response' => $response_body
			));
		}

		exit;
	}
	/**
	 * Save Customer Card Details.
	 *
	 * @param $cepta_response
	 * @param $user_id
	 * @param $order_id
	 */
	public function save_card_details($cepta_response, $user_id, $order_id)
	{

		$this->save_subscription_payment_token($order_id, $cepta_response);

		$order = wc_get_order($order_id);

		$save_card = $order->get_meta('_wc_cepta_save_card');

		if ($user_id && $this->saved_cards && $save_card && $cepta_response->data->authorization->reusable && 'card' == $cepta_response->data->authorization->channel) {

			$gateway_id = $order->get_payment_method();

			$last4          = $cepta_response->data->authorization->last4;
			$exp_year       = $cepta_response->data->authorization->exp_year;
			$brand          = $cepta_response->data->authorization->card_type;
			$exp_month      = $cepta_response->data->authorization->exp_month;
			$auth_code      = $cepta_response->data->authorization->authorization_code;
			$customer_email = $cepta_response->data->customer->email;

			$payment_token = "$auth_code###$customer_email";

			$token = new WC_Payment_Token_CC();
			$token->set_token($payment_token);
			$token->set_gateway_id($gateway_id);
			$token->set_card_type(strtolower($brand));
			$token->set_last4($last4);
			$token->set_expiry_month($exp_month);
			$token->set_expiry_year($exp_year);
			$token->set_user_id($user_id);
			$token->save();

			$order->delete_meta_data('_wc_cepta_save_card');
			$order->save();
		}
	}

	/**
	 * Save payment token to the order for automatic renewal for further subscription payment.
	 *
	 * @param $order_id
	 * @param $cepta_response
	 */
	public function save_subscription_payment_token($order_id, $cepta_response)
	{

		if (!function_exists('wcs_order_contains_subscription')) {
			return;
		}

		if ($this->order_contains_subscription($order_id) && $cepta_response->data->authorization->reusable && 'card' == $cepta_response->data->authorization->channel) {

			$auth_code      = $cepta_response->data->authorization->authorization_code;
			$customer_email = $cepta_response->data->customer->email;

			$payment_token = "$auth_code###$customer_email";

			// Also store it on the subscriptions being purchased or paid for in the order
			if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) {

				$subscriptions = wcs_get_subscriptions_for_order($order_id);
			} elseif (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order_id)) {

				$subscriptions = wcs_get_subscriptions_for_renewal_order($order_id);
			} else {

				$subscriptions = array();
			}

			if (empty($subscriptions)) {
				return;
			}

			foreach ($subscriptions as $subscription) {
				$subscription->update_meta_data('_cepta_token', $payment_token);
				$subscription->save();
			}
		}
	}

	/**
	 * Get custom fields to pass to Cepat.
	 *
	 * @param int $order_id WC Order ID
	 *
	 * @return array
	 */
	public function get_custom_fields($order_id)
	{

		$order = wc_get_order($order_id);

		$custom_fields = array();

		$custom_fields[] = array(
			'display_name'  => 'Plugin',
			'variable_name' => 'plugin',
			'value'         => 'woo-cepta',
		);

		if ($this->custom_metadata) {

			if ($this->meta_order_id) {

				$custom_fields[] = array(
					'display_name'  => 'Order ID',
					'variable_name' => 'order_id',
					'value'         => $order_id,
				);
			}

			if ($this->meta_name) {

				$custom_fields[] = array(
					'display_name'  => 'Customer Name',
					'variable_name' => 'customer_name',
					'value'         => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				);
			}

			if ($this->meta_email) {

				$custom_fields[] = array(
					'display_name'  => 'Customer Email',
					'variable_name' => 'customer_email',
					'value'         => $order->get_billing_email(),
				);
			}

			if ($this->meta_phone) {

				$custom_fields[] = array(
					'display_name'  => 'Customer Phone',
					'variable_name' => 'customer_phone',
					'value'         => $order->get_billing_phone(),
				);
			}

			if ($this->meta_products) {

				$line_items = $order->get_items();

				$products = '';

				foreach ($line_items as $item_id => $item) {
					$name     = $item['name'];
					$quantity = $item['qty'];
					$products .= $name . ' (Qty: ' . $quantity . ')';
					$products .= ' | ';
				}

				$products = rtrim($products, ' | ');

				$custom_fields[] = array(
					'display_name'  => 'Products',
					'variable_name' => 'products',
					'value'         => $products,
				);
			}

			if ($this->meta_billing_address) {

				$billing_address = $order->get_formatted_billing_address();
				$billing_address = esc_html(preg_replace('#<br\s*/?>#i', ', ', $billing_address));

				$cepta_params['meta_billing_address'] = $billing_address;

				$custom_fields[] = array(
					'display_name'  => 'Billing Address',
					'variable_name' => 'billing_address',
					'value'         => $billing_address,
				);
			}

			if ($this->meta_shipping_address) {

				$shipping_address = $order->get_formatted_shipping_address();
				$shipping_address = esc_html(preg_replace('#<br\s*/?>#i', ', ', $shipping_address));

				if (empty($shipping_address)) {

					$billing_address = $order->get_formatted_billing_address();
					$billing_address = esc_html(preg_replace('#<br\s*/?>#i', ', ', $billing_address));

					$shipping_address = $billing_address;
				}
				$custom_fields[] = array(
					'display_name'  => 'Shipping Address',
					'variable_name' => 'shipping_address',
					'value'         => $shipping_address,
				);
			}
		}

		return $custom_fields;
	}

	/**
	 * Checks if WC version is less than passed in version.
	 *
	 * @param string $version Version to check against.
	 *
	 * @return bool
	 */
	public function is_wc_lt($version)
	{
		return version_compare(WC_VERSION, $version, '<');
	}

	/**
	 * Checks if autocomplete order is enabled for the payment method.
	 *
	 * @since 5.7
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	protected function is_autocomplete_order_enabled($order)
	{
		$autocomplete_order = false;

		$payment_method = $order->get_payment_method();

		$cepta_settings = get_option('woocommerce_' . $payment_method . '_settings');

		if (isset($cepta_settings['autocomplete_order']) && 'yes' === $cepta_settings['autocomplete_order']) {
			$autocomplete_order = true;
		}

		return $autocomplete_order;
	}

	/**
	 * Retrieve the payment channels configured for the gateway
	 *
	 * @since 
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	protected function get_gateway_payment_channels($order)
	{

		$payment_method = $order->get_payment_method();

		if ('cepta' === $payment_method) {
			return array();
		}

		$payment_channels = $this->payment_channels;

		if (empty($payment_channels)) {
			$payment_channels = array('card');
		}

		return $payment_channels;
	}

	/**
	 * Retrieve a transaction from cepta
	 * @since 
	 * @param $cepta_txn_ref
	 * @return false|mixed
	 */


	private function get_cepta_transaction($cepta_txn_ref)
	{

		$cepta_url = 'https://adapter.cepta.co/api/v1/pay/verify-payment' . $cepta_txn_ref;

		$is_test_mode = $this->testmode ?? true; // Default to true if not defined
		$public_key   = $is_test_mode ? $this->test_public_key : $this->live_public_key;
		$secret_key   = $is_test_mode ? $this->test_secret_key : $this->live_secret_key;
		// $base_url     = 'https://dev-adapter.cepta.co'; // Keep constant for this endpoint as per code

		$ts           = time();
		// $method       = 'GET';
		// $path         = '/api/v1/pay/confirm-status';
		// $full_url     = $base_url . $path . '?TransactionRef=' . urlencode($hydrogenTransactionRef);

		// $payload_string = $ts . $method . $path; // GET request body is empty
		// $signature      = hash_hmac('sha256', $payload_string, $secret_key);

		$headers = array(
			'Accept'             => 'application/json',
			'X-Access-Key'       => $public_key,
			'X-Access-Ts'        => $ts,
			// 'X-Access-Signature' => $signature,
			'Cache-Control'      => 'no-cache',
		);

		$args = array(
			'method'      => 'GET',
			'headers' => $headers,
			'timeout' => 60,
		);

		$request = wp_remote_get($cepta_url, $args);

		if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
			return json_decode(wp_remote_retrieve_body($request));
		}

		return false;
	}

	/**
	 * Get Cepta payment icon URL.
	 */
	public function get_logo_url()
	{

		$base_location = wc_get_base_location();

		if ('GH' === $base_location['country']) {
			$url = WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-gh.png', WC_CEPTA_MAIN_FILE));
		} elseif ('ZA' === $base_location['country']) {
			$url = WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-za.png', WC_CEPTA_MAIN_FILE));
		} elseif ('KE' === $base_location['country']) {
			$url = WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-ke.png', WC_CEPTA_MAIN_FILE));
		} else {
			$url = WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-wc.png', WC_CEPTA_MAIN_FILE));
		}
		return apply_filters('wc_cepta_gateway_icon_url', $url, $this->id);
	}

	/**
	 * Check if an order contains a subscription.
	 *
	 * @param int $order_id WC Order ID.
	 *
	 * @return bool
	 */
	public function order_contains_subscription($order_id)
	{

		return function_exists('wcs_order_contains_subscription') && (wcs_order_contains_subscription($order_id) || wcs_order_contains_renewal($order_id));
	}
}
