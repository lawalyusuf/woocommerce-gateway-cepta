Cepta WooCommerce Gateway- settings, checkout, redirect/inline, popup verify, tokens, subscriptions, icons<?php

																											/**
																											 * Cepta WooCommerce Gateway
																											 * - settings, checkout, redirect/inline, popup verify, tokens, subscriptions, icons
																											 * 
																											 */

																											if (! defined('ABSPATH')) {
																												exit;
																											}

																											class WC_Gateway_Cepta extends WC_Payment_Gateway_CC
																											{

																												// --- Settings / flags ---
																												public $testmode;
																												public $autocomplete_order;
																												public $payment_page;

																												// --- Keys ---
																												public $test_public_key;
																												public $test_secret_key;
																												public $live_public_key;
																												public $live_secret_key;

																												// --- Features ---
																												public $saved_cards;
																												public $split_payment;
																												public $remove_cancel_order_button;
																												public $custom_metadata;

																												// --- Split payment ---
																												public $subaccount_code;
																												public $charges_account;
																												public $transaction_charges;

																												// --- Metadata toggles ---
																												public $meta_order_id;
																												public $meta_name;
																												public $meta_email;
																												public $meta_phone;
																												public $meta_billing_address;
																												public $meta_shipping_address;
																												public $meta_products;

																												// --- Resolved keys ---
																												public $public_key;
																												public $secret_key;

																												// --- State ---
																												public $msg;

																												public function __construct()
																												{
																													$this->id                 = 'cepta';
																													$this->method_title       = __('Cepta Payment Gateway', 'woo-cepta');
																													$this->method_description = sprintf(
																														/* translators: 1: signup url, 2: token url */
																														__('Cepta helps you process cards and account transfers. <a href="%1$s" target="_blank">Sign up</a> and <a href="%2$s" target="_blank">get your token</a>.', 'cepta-woocommerce'),
																														'https://app.cepta.co/home/',
																														'https://app.cepta.co/home'
																													);
																													$this->has_fields = true;

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

																													$this->init_form_fields();
																													$this->init_settings();

																													// Options
																													$this->title              = $this->get_option('title');
																													$this->description        = $this->get_option('description');
																													$this->enabled            = $this->get_option('enabled');
																													$this->payment_page       = $this->get_option('payment_page');
																													$this->testmode           = ('yes' === $this->get_option('testmode'));
																													$this->autocomplete_order = ('yes' === $this->get_option('autocomplete_order'));

																													$this->test_public_key = $this->get_option('test_public_key');
																													$this->test_secret_key = $this->get_option('test_secret_key');
																													$this->live_public_key = $this->get_option('live_public_key');
																													$this->live_secret_key = $this->get_option('live_secret_key');

																													// $this->saved_cards                = ('yes' === $this->get_option('saved_cards'));
																													$this->split_payment              = ('yes' === $this->get_option('split_payment'));
																													$this->remove_cancel_order_button = ('yes' === $this->get_option('remove_cancel_order_button'));

																													$this->subaccount_code     = $this->get_option('subaccount_code');
																													$this->charges_account     = $this->get_option('split_payment_charge_account');
																													$this->transaction_charges = $this->get_option('split_payment_transaction_charge');

																													$this->custom_metadata       = ('yes' === $this->get_option('custom_metadata'));
																													$this->meta_order_id         = ('yes' === $this->get_option('meta_order_id'));
																													$this->meta_name             = ('yes' === $this->get_option('meta_name'));
																													$this->meta_email            = ('yes' === $this->get_option('meta_email'));
																													$this->meta_phone            = ('yes' === $this->get_option('meta_phone'));
																													$this->meta_billing_address  = ('yes' === $this->get_option('meta_billing_address'));
																													$this->meta_shipping_address = ('yes' === $this->get_option('meta_shipping_address'));
																													$this->meta_products         = ('yes' === $this->get_option('meta_products'));

																													$this->public_key = $this->testmode ? $this->test_public_key : $this->live_public_key;
																													$this->secret_key = $this->testmode ? $this->test_secret_key : $this->live_secret_key;

																													// Hooks
																													add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
																													add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
																													add_action('admin_notices', array($this, 'admin_notices'));
																													add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
																													add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

																													// API endpoints
																													add_action('woocommerce_api_cep_wc_cepta_webhook', array($this, 'process_webhooks'));                 // reserved
																													add_action('woocommerce_api_cepta_wc_payment', array($this, 'cepta_wc_payment_popup_action'));        // reserved
																													add_action('woocommerce_api_wc_gateway_cepta', array($this, 'verify_cepta_wc_transaction'));         // reserved
																													add_action('woocommerce_api_wc_gateway_cepta_popup', array($this, 'verify_cepta_wc_transaction_popup'));

																													if (! $this->is_valid_for_use()) {
																														$this->enabled = false;
																													}
																												}

																												// --- Capability checks ---

																												public function is_valid_for_use()
																												{
																													// Retrieve ALL currency codes supported by WooCommerce (e.g., USD, EUR, NGN, etc.)
																													$supported = array_keys(get_woocommerce_currencies());
																													$supported = apply_filters('woocommerce_cepta_supported_currencies', $supported);
																													if (!in_array(get_woocommerce_currency(), $supported, true)) {
																														$this->msg = sprintf(
																															/* translators: %s: general settings url */
																															__('Cepta does not support your store currency. Please check your currency settings <a href="%s">here</a>.', 'woo-cepta'),
																															admin_url('admin.php?page=wc-settings&tab=general')
																														);
																														return false;
																													}

																													return true;
																												}

																												public function is_available()
																												{
																													if ('yes' !== $this->enabled) {
																														return false;
																													}
																													if (empty($this->secret_key)) {
																														return false;
																													}
																													return true;
																												}

																												// --- UI (icons/admin) ---

																												public function get_icon()
																												{
																													$country = wc_get_base_location()['country'];
																													if ('GH' === $country) {
																														$icon = '<img src="' . esc_url(WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-gh.png', WC_CEPTA_MAIN_FILE))) . '" alt="' . esc_attr__('Cepta Payment Options', 'woo-cepta') . '" />';
																													} elseif ('ZA' === $country) {
																														$icon = '<img src="' . esc_url(WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-za.png', WC_CEPTA_MAIN_FILE))) . '" alt="' . esc_attr__('Cepta Payment Options', 'woo-cepta') . '" />';
																													} elseif ('KE' === $country) {
																														$icon = '<img src="' . esc_url(WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-ke.png', WC_CEPTA_MAIN_FILE))) . '" alt="' . esc_attr__('Cepta Payment Options', 'woo-cepta') . '" />';
																													} else {
																														$icon = '<img src="' . esc_url(WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-wc.png', WC_CEPTA_MAIN_FILE))) . '" alt="' . esc_attr__('Cepta Payment Options', 'woo-cepta') . '" />';
																													}
																													return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
																												}

																												public function admin_notices()
																												{
																													if ('no' === $this->enabled) {
																														return;
																													}
																													if (! ($this->public_key || $this->secret_key)) {
																														echo '<div class="error"><p>' . wp_kses_post(
																															sprintf(
																																/* translators: %s settings url */
																																__('Please enter your Cepta merchant details %s to use the Cepta WooCommerce plugin.', 'woo-cepta'),
																																'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=cepta')) . '">' . esc_html__('here', 'woo-cepta') . '</a>'
																															)
																														) . '</p></div>';
																													}
																												}

																												public function admin_options()
																												{ ?>
<h2><?php esc_html_e('Cepta Payment Gateway', 'woo-cepta'); ?></h2>
<h2>
	<?php
																													if (function_exists('wc_back_link')) {
																														wc_back_link(esc_html__('Return to payments', 'woo-cepta'), esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')));
																													}
	?>
</h2>
<h4>
	<?php
																													printf(
																														wp_kses_post(__('Optional: Set your webhook URL <a href="%1$s" target="_blank" rel="noopener noreferrer">here</a> to the URL below <span style="color:red"><pre><code>%2$s</code></pre></span>', 'cepta-wc')),
																														esc_url('#'),
																														esc_html(WC()->api_request_url('cepta-wc_webhook'))
																													);
	?>
</h4>
<?php if ($this->is_valid_for_use()) : ?>
	<table class="form-table"><?php $this->generate_settings_html(); ?></table>
<?php else : ?>
	<div class="inline error">
		<p><strong><?php esc_html_e('Cepta Payment Gateway Disabled', 'woo-cepta'); ?></strong>: <?php echo esc_html($this->msg); ?></p>
	</div>
<?php endif;
																												}

																												// --- Settings form ---

																												public function init_form_fields()
																												{
																													$fields = array(
																														'enabled' => array(
																															'title'       => __('Enable/Disable', 'woo-cepta'),
																															'label'       => __('Enable cepta', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'description' => __('Enable Cepta on checkout.', 'cepta-woocommerce-payment'),
																															'default'     => 'no',
																															'desc_tip'    => true,
																														),
																														'title' => array(
																															'title'       => __('Title', 'cepta-woocommerce-payment'),
																															'type'        => 'text',
																															'description' => __('Payment method title shown on checkout.', 'cepta-woocommerce-payment'),
																															'default'     => __('Cepta Payment Gateway', 'cepta-woocommerce-payment'),
																															'desc_tip'    => true,
																														),
																														'description' => array(
																															'title'       => __('Description', 'cepta-wc'),
																															'type'        => 'textarea',
																															'description' => __('Shown to customers at checkout.', 'cepta-wc'),
																															'default'     => __('Powered by Cepta. Accepts Mastercard, Visa, Verve.', 'cepta-wc'),
																															'desc_tip'    => true,
																														),
																														'testmode' => array(
																															'title'       => __('Test mode', 'woo-cepta'),
																															'label'       => __('Enable Test Mode', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'description' => __('Test payments before going live. Disable when live.', 'woo-cepta'),
																															'default'     => 'yes',
																															'desc_tip'    => true,
																														),
																														'payment_page' => array(
																															'title'       => __('Payment Option', 'cepta-wc'),
																															'type'        => 'select',
																															'description' => __('Inline Checkout lets customers pay directly on your site, while Redirect takes them to Cepta to complete payment.', 'woo-cepta'),
																															'default'     => '',
																															'options'     => array(
																																''         => __('Select One', 'woo-cepta'),
																																'inline'   => __('Inline Checkout', 'woo-cepta'),
																																'redirect' => __('Redirect', 'woo-cepta'),
																															),
																														),
																														'test_secret_key' => array(
																															'title'       => __('Sandbox/Test Secret Key', 'woo-cepta'),
																															'type'        => 'password',
																															'description' => __('Enter your Sandbox/Test Secret Key', 'woo-cepta'),
																															'default'     => '',
																														),
																														'test_public_key' => array(
																															'title'       => __('Test Public Key', 'woo-cepta'),
																															'type'        => 'password',
																															'description' => __('Enter your Test Public Key', 'woo-cepta'),
																															'default'     => '',
																														),
																														'live_secret_key' => array(
																															'title'       => __('Live Secret Key', 'woo-cepta'),
																															'type'        => 'password',
																															'description' => __('Enter your Live Secret Key', 'woo-cepta'),
																															'default'     => '',
																														),
																														'live_public_key' => array(
																															'title'       => __('Live Public Key', 'woo-cepta'),
																															'type'        => 'password',
																															'description' => __('Enter your Live Public Key', 'woo-cepta'),
																															'default'     => '',
																														),
																														'autocomplete_order' => array(
																															'title'       => __('Autocomplete Order After Payment', 'woo-cepta'),
																															'label'       => __('Autocomplete Order', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'class'       => 'wc-cepta-autocomplete-order',
																															'description' => __('Mark order complete after successful payment.', 'woo-cepta'),
																															'default'     => 'no',
																															'desc_tip'    => true,
																														),
																														'remove_cancel_order_button' => array(
																															'title'       => __('Remove Cancel Order & Restore Cart Button', 'woo-cepta'),
																															'label'       => __('Remove the cancel/restore button on pay page', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'default'     => 'no',
																														),
																														// 'saved_cards' => array(
																														// 	'title'       => __('Saved Cards', 'woo-cepta'),
																														// 	'label'       => __('Enable Payment via Saved Cards', 'woo-cepta'),
																														// 	'type'        => 'checkbox',
																														// 	'description' => __('Cards saved on Cepta (not your store). SSL required.', 'woo-cepta'),
																														// 	'default'     => 'no',
																														// 	'desc_tip'    => true,
																														// ),
																														'custom_metadata' => array(
																															'title'       => __('Custom Metadata', 'woo-cepta'),
																															'label'       => __('Enable Custom Metadata', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'class'       => 'wc-cepta-metadata',
																															'description' => __('Send additional order info to Cepta.', 'woo-cepta'),
																															'default'     => 'no',
																															'desc_tip'    => true,
																														),
																														'meta_order_id' => array(
																															'title'       => __('Order ID', 'woo-cepta'),
																															'label'       => __('Send Order ID', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'class'       => 'wc-cepta-meta-order-id',
																															'default'     => 'no',
																															'desc_tip'    => true,
																														),
																														'meta_name' => array(
																															'title'       => __('Customer Name', 'woo-cepta'),
																															'label'       => __('Send Customer Name', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'class'       => 'wc-cepta-meta-name',
																															'default'     => 'no',
																															'desc_tip'    => true,
																														),
																														'meta_email' => array(
																															'title'       => __('Customer Email', 'woo-cepta'),
																															'label'       => __('Send Customer Email', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'class'       => 'wc-cepta-meta-email',
																															'default'     => 'no',
																															'desc_tip'    => true,
																														),
																														'meta_phone' => array(
																															'title'       => __('Customer Phone', 'woo-cepta'),
																															'label'       => __('Send Customer Phone', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'class'       => 'wc-cepta-meta-phone',
																															'default'     => 'no',
																															'desc_tip'    => true,
																														),
																														'meta_billing_address' => array(
																															'title'       => __('Order Billing Address', 'woo-cepta'),
																															'label'       => __('Send Order Billing Address', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'class'       => 'wc-cepta-meta-billing-address',
																															'default'     => 'no',
																															'desc_tip'    => true,
																														),
																														'meta_shipping_address' => array(
																															'title'       => __('Order Shipping Address', 'woo-cepta'),
																															'label'       => __('Send Order Shipping Address', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'class'       => 'wc-cepta-meta-shipping-address',
																															'default'     => 'no',
																															'desc_tip'    => true,
																														),
																														'meta_products' => array(
																															'title'       => __('Product(s) Purchased', 'woo-cepta'),
																															'label'       => __('Send Product(s) Purchased', 'woo-cepta'),
																															'type'        => 'checkbox',
																															'class'       => 'wc-cepta-meta-products',
																															'default'     => 'no',
																															'desc_tip'    => true,
																														),
																													);

																													if ('NGN' !== get_woocommerce_currency()) {
																														unset($fields['custom_gateways']);
																													}

																													$this->form_fields = $fields;
																												}

																												// --- Checkout form ---

																												public function payment_fields()
																												{
																													if ($this->description) {
																														echo wp_kses_post(wpautop(wptexturize($this->description)));
																													}
																													if (! is_ssl()) {
																														return;
																													}
																													wp_nonce_field('wc_cepta_payment_nonce_action', 'wc_cepta_payment_nonce');

																													if ($this->supports('tokenization') && is_checkout() && $this->saved_cards && is_user_logged_in()) {
																														$this->tokenization_script();
																														$this->saved_payment_methods();
																														$this->save_payment_method_checkbox();
																													}
																												}

																												// --- Frontend/admin scripts ---

																												public function payment_scripts()
																												{
																													if (isset($_GET['pay_for_order']) || ! is_checkout_pay_page()) {
																														return;
																													}
																													if ('no' === $this->enabled) {
																														return;
																													}
																													if (! isset($_GET['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'wc_cepta_payment_nonce')) {
																														wp_die(esc_html__('Invalid request. Nonce verification failed.', 'text-domain'), esc_html__('Error', 'text-domain'), array('response' => 403));
																													}

																													$order_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
																													$order_id  = absint(get_query_var('order-pay'));
																													$order     = wc_get_order($order_id);
																													if (! $order || $this->id !== $order->get_payment_method()) {
																														return;
																													}

																													$script_src = $this->testmode
																														? 'https://lawalyusuf.github.io/ceptest/cep.js'
																														: 'https://lawalyusuf.github.io/ceptest/cep.js';

																													$public_key = $this->testmode ? $this->test_public_key : $this->live_public_key;
																													$secret_key = $this->testmode ? $this->test_secret_key : $this->live_secret_key;
																													$base_url   = 'https://adapter.cepta.co';

																													$suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

																													wp_enqueue_script('jquery');
																													wp_enqueue_script('cepta', $script_src, array('jquery'), WC_CEPTA_VERSION, false);
																													wp_enqueue_script('wc_cepta', plugins_url('assets/js/cepta' . $suffix . '.js', WC_CEPTA_MAIN_FILE), array('jquery', 'cepta'), WC_CEPTA_VERSION, false);

																													$cepta_params = array(
																														'public_key' => $public_key,
																														'secret_key' => $secret_key,
																														'base_url'   => $base_url,
																														'key'        => $secret_key,
																														'nonce'      => wp_create_nonce('wc_cepta_payment_nonce'),
																													);

																													if (is_checkout_pay_page() && get_query_var('order-pay')) {
																														$email         = $order->get_billing_email();
																														$amount        = $order->get_total();
																														$txnref        = $order_id . '_' . time();
																														$the_order_id  = $order->get_id();
																														$the_order_key = $order->get_order_key();
																														$currency      = $order->get_currency();

																														if ((int) $the_order_id === (int) $order_id && $the_order_key === $order_key) {
																															$cepta_params['email']                 = $email;
																															$cepta_params['amount']                = $amount;
																															$cepta_params['txnref']                = $txnref;
																															$cepta_params['currency']              = $currency;
																															$cepta_params['cepta_wc_redirect_url'] = wc_get_page_permalink('myaccount');
																														}

																														if ($this->split_payment) {
																															$cepta_params['subaccount_code']     = $this->subaccount_code;
																															$cepta_params['charges_account']     = $this->charges_account;
																															$cepta_params['transaction_charges'] = empty($this->transaction_charges) ? '' : ($this->transaction_charges * 100);
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
																																$items    = $order->get_items();
																																$products = array();
																																foreach ($items as $item) {
																																	$products[] = sprintf('%s (Qty: %d)', $item['name'], $item['qty']);
																																}
																																$cepta_params['meta_products'] = implode(' | ', $products);
																															}
																															if ($this->meta_billing_address) {
																																$billing = esc_html(preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_billing_address()));
																																$cepta_params['meta_billing_address'] = $billing;
																															}
																															if ($this->meta_shipping_address) {
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

																													wp_localize_script('wc_cepta', 'wc_cepta_params', $cepta_params);
																												}

																												public function admin_scripts()
																												{
																													$screen = get_current_screen();
																													if (! $screen || 'woocommerce_page_wc-settings' !== $screen->id) {
																														return;
																													}
																													$suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
																													wp_enqueue_script('wc_cepta_admin', plugins_url('assets/js/cepta-admin' . $suffix . '.js', WC_CEPTA_MAIN_FILE), array(), WC_CEPTA_VERSION, true);
																													wp_localize_script('wc_cepta_admin', 'wc_cepta_admin_params', array('plugin_url' => WC_CEPTA_URL));
																												}

																												// --- Process payment ---

																												public function process_payment($order_id)
																												{
																													if (
																														! isset($_POST['wc_cepta_payment_nonce']) ||
																														! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wc_cepta_payment_nonce'])), 'wc_cepta_payment_nonce_action')
																													) {
																														wc_add_notice(__('Security check failed, please try again.', 'woocommerce'), 'error');
																														return;
																													}

																													if ('redirect' === $this->payment_page) {
																														return $this->process_redirect_payment_option($order_id);
																													}

																													// Token flow
																													if (isset($_POST['wc-' . $this->id . '-payment-token']) && 'new' !== $_POST['wc-' . $this->id . '-payment-token']) {
																														$token_id = sanitize_text_field(wp_unslash($_POST['wc-' . $this->id . '-payment-token']));
																														$token    = \WC_Payment_Tokens::get($token_id);
																														if (! $token || $token->get_user_id() !== get_current_user_id()) {
																															wc_add_notice(__('Invalid token ID', 'woo-cepta'), 'error');
																															return;
																														}
																														$status = $this->process_token_payment($token->get_token(), $order_id); // existing WC method in parent
																														if ($status) {
																															$order = wc_get_order($order_id);

																															$nonce = wp_create_nonce('wc_cepta_payment_nonce');
																															WC()->session->set('wc_cepta_payment_nonce', $nonce);
																															$order->update_meta_data('_wc_cepta_payment_nonce', $nonce);
																															$order->save();

																															$redirect_url = add_query_arg(array('nonce' => $nonce), $this->get_return_url($order));
																															return array('result' => 'success', 'redirect' => $redirect_url);
																														}
																														return;
																													}

																													// Normal flow
																													$order = wc_get_order($order_id);
																													if (is_user_logged_in() && $this->saved_cards && isset($_POST['wc-' . $this->id . '-new-payment-method']) && (bool) $_POST['wc-' . $this->id . '-new-payment-method']) {
																														$order->update_meta_data('_wc_cepta_save_card', true);
																														$order->save();
																													}

																													$nonce = wp_create_nonce('wc_cepta_payment_nonce');
																													WC()->session->set('wc_cepta_payment_nonce', $nonce);
																													$order->update_meta_data('_wc_cepta_payment_nonce', $nonce);
																													$order->save();

																													$redirect_url = add_query_arg(array('nonce' => $nonce), $order->get_checkout_payment_url(true));
																													return array('result' => 'success', 'redirect' => $redirect_url);
																												}

																												// --- Redirect API init ---

																												public function process_redirect_payment_option($order_id)
																												{
																													if (
																														! isset($_POST['wc_cepta_payment_nonce']) ||
																														! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wc_cepta_payment_nonce'])), 'wc_cepta_payment_nonce_action')
																													) {
																														wc_add_notice(__('Security check failed, please try again.', 'woocommerce'), 'error');
																														return;
																													}

																													$order       = wc_get_order($order_id);
																													$amount_raw  = (float) $order->get_total();
																													$amount      = (int) round($amount_raw);
																													$min_amount  = 50;

																													if ($amount < $min_amount) {
																														wc_add_notice(__('Min amount cannot be less than 50 Naira. Please add more product.', 'woo-cepta'), 'error');
																														return;
																													}

																													$txnref       = 'CEP_' . $order_id . '_' . time();
																													$ts           = time();
																													$path         = '/api/v1/pay';
																													$method       = 'POST';
																													$nonce        = wp_create_nonce('wc_cepta_payment_nonce');
																													$public_key   = $this->testmode ? $this->test_public_key : $this->live_public_key;
																													$secret_key   = $this->testmode ? $this->test_secret_key : $this->live_secret_key;
																													$cepta_url    = 'https://adapter.cepta.co/api/v1/pay';
																													$callback_url = add_query_arg('nonce', $nonce, $order->get_checkout_payment_url(true));

																													$payload = array(
																														'amount'               => $amount,
																														'currency'             => $order->get_currency(),
																														'description'          => 'Payment for order ID ' . $order->get_id(),
																														'pageName'             => '',
																														'transactionReference' => $txnref,
																														'customerEmail'        => $order->get_billing_email(),
																														'customUrlText'        => '',
																														'callbackUrl'          => $callback_url,
																														'isPlugin'             => true,
																													);

																													// Channels (optional)
																													$channels = $this->get_gateway_payment_channels($order);
																													if (! empty($channels)) {
																														$payload['channels'] = $channels;
																													}

																													$payload_body   = wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
																													if (false === $payload_body) {
																														wc_add_notice(__('Error: Could not encode payment parameters to JSON.', 'woo-cepta'), 'error');
																														return;
																													}
																													$signature      = hash_hmac('sha256', $ts . $method . $path . $payload_body, $secret_key);

																													$order->update_meta_data('_cepta_txn_ref', $txnref);
																													$order->save();

																													$headers = array(
																														'Accept'             => 'application/json',
																														'Content-Type'       => 'application/json',
																														'X-Access-Key'       => $public_key,
																														'X-Access-Ts'        => $ts,
																														'X-Access-Signature' => $signature,
																														'Cache-Control'      => 'no-cache',
																													);

																													$args     = array('headers' => $headers, 'timeout' => 60, 'body' => $payload_body, 'data_format' => 'body');
																													$request  = wp_remote_post($cepta_url, $args);

																													if (is_wp_error($request)) {
																														wc_add_notice(sprintf(__('Unable to connect to Cepta payment gateway. Error: %s', 'woo-cepta'), $request->get_error_message()), 'error');
																														return;
																													}

																													$code = (int) wp_remote_retrieve_response_code($request);
																													$raw  = wp_remote_retrieve_body($request);
																													$body = json_decode($raw);

																													if (200 === $code && isset($body->data->paymentUrl)) {
																														return array('result' => 'success', 'redirect' => $body->data->paymentUrl);
																													}

																													$msg = isset($body->message) ? $body->message : 'Unknown API error.';
																													wc_add_notice(sprintf(__('Payment initiation failed. Error: %s (Code: %d)', 'woo-cepta'), $msg, $code), 'error');
																													return;
																												}

																												// --- Receipt page ---

																												public function receipt_page($order_id)
																												{
																													$order = wc_get_order($order_id);
																													echo '<div id="wc-cepta-form">';
																													echo '<p>' . esc_html__('Thank you for your order, please click the button below to pay with Cepta Payment Gateway.', 'cepta-wc') . '</p>';
																													echo '<div id="cepta_form"><form id="order_review" method="post" action="' . esc_url(WC()->api_request_url('WC_Gateway_Cepta')) . '"></form><button class="button" id="cepta-payment-button">' . esc_html__('Pay Now', 'woo-cepta') . '</button>';
																													if (! $this->remove_cancel_order_button) {
																														echo '  <a class="button cancel" id="cepta-cancel-payment-button" href="' . esc_url($order->get_cancel_order_url()) . '">' . esc_html__('Cancel order &amp; restore cart', 'woo-cepta') . '</a></div>';
																													}
																													echo '</div>';
																												}

																												// --- Ajax popup verification ---

																												public function verify_cepta_wc_transaction_popup()
																												{
																													if (
																														! isset($_POST['wc_cepta_payment_nonce']) ||
																														! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wc_cepta_payment_nonce'])), 'wc_cepta_payment_nonce')
																													) {
																														wp_send_json(array('result' => 'error', 'message' => __('Security check failed, please try again.', 'woocommerce')));
																														return;
																													}
																													if (! isset($_POST['transactionRef'], $_POST['ceptaOderId'])) {
																														wp_send_json(array('result' => 'error', 'message' => 'TransactionRef or OrderId not found in POST data.'));
																														return;
																													}

																													$txn_ref  = sanitize_text_field(wp_unslash($_POST['transactionRef']));
																													$order_id = (int) sanitize_text_field(wp_unslash($_POST['ceptaOderId']));
																													$order    = wc_get_order($order_id);

																													if (! $order) {
																														wp_send_json(array('statusRes' => false, 'status' => 'error', 'message' => 'Order not found for verification.'));
																														return;
																													}

																													$is_test   = $this->testmode ?? true;
																													$pub_key   = $is_test ? $this->test_public_key : $this->live_public_key;
																													$sec_key   = $is_test ? $this->test_secret_key : $this->live_secret_key;
																													$base_url  = 'https://adapter.cepta.co';
																													$site_url  = get_site_url();
																													$ts        = time();
																													$method    = 'GET';
																													$path      = '/api/v1/pay/confirm-status';
																													$full_url  = $base_url . $path . '?TransactionRef=' . urlencode($txn_ref);

																													$signature = hash_hmac('sha256', $ts . $method . $path, $sec_key);
																													$headers   = array(
																														'Accept'             => 'application/json',
																														'X-Access-Key'       => $pub_key,
																														'X-Access-Ts'        => $ts,
																														'X-Access-Signature' => $signature,
																														'Cache-Control'      => 'no-cache',
																														'Referer'            => $site_url,
																														'Origin'             => $site_url,
																													);
																													$args      = array('method' => 'GET', 'headers' => $headers, 'timeout' => 60);
																													$response  = wp_remote_get($full_url, $args);

																													if (is_wp_error($response)) {
																														$order->update_status('failed', sprintf(__('Payment verification failed due to network error: %s', 'woo-cepta'), $response->get_error_message()));
																														wp_send_json(array('result' => 'error', 'message' => $response->get_error_message()));
																														return;
																													}

																													$code = (int) wp_remote_retrieve_response_code($response);
																													$body = wp_remote_retrieve_body($response);
																													$data = json_decode($body);

																													if (200 === $code && isset($data->data->status)) {
																														$status = $data->data->status;

																														if ('Successful' === $status) {
																															// already processed
																															if (in_array($order->get_status('edit'), array('processing', 'completed', 'on-hold'), true)) {
																																WC()->cart->empty_cart();
																																wp_send_json(array('statusRes' => true, 'status' => 'success', 'message' => 'Order already processed.', 'redirect' => $order->get_checkout_order_received_url()));
																															}

																															$order_total     = (float) $order->get_total();
																															$order_currency  = $order->get_currency();
																															$amount_paid     = isset($data->data->amount) ? (float) $data->data->amount : 0.0;
																															$payment_currency = isset($data->data->currency) ? strtoupper($data->data->currency) : $order_currency;

																															if ($amount_paid < $order_total) {
																																$order->update_status('on-hold', __('Amount paid is less than the total order amount.', 'woo-cepta'));
																																$order->add_order_note(sprintf(__('Amount Paid was %1$s while order total is %2$s', 'woo-cepta'), $amount_paid, $order_total));
																																$notice = __('Payment successful but amount less than order total. Order on hold.', 'woo-cepta');
																															} elseif ($payment_currency !== $order_currency) {
																																$order->update_status('on-hold', __('Payment currency differs from order currency.', 'woo-cepta'));
																																$order->add_order_note(sprintf(__('Order currency is %1$s; payment currency is %2$s', 'woo-cepta'), $order_currency, $payment_currency));
																																$notice = __('Payment successful but currency mismatch. Order on hold.', 'woo-cepta');
																															} else {
																																$order->payment_complete($txn_ref);
																																$order->add_order_note(sprintf(__('Payment via Cepta Successful (Transaction Reference: %s)', 'woo-cepta'), $txn_ref));
																																if ($this->is_autocomplete_order_enabled($order)) {
																																	$order->update_status('completed', '');
																																}
																																$notice = __('Thank you. Your payment was successful.', 'woo-cepta');
																															}

																															$order->update_meta_data('_transaction_id', $txn_ref);
																															$order->save();

																															$this->save_card_details($data, $order->get_user_id(), $order_id);
																															WC()->cart->empty_cart();
																															wc_add_notice($notice, 'notice');

																															wp_send_json(array(
																																'statusRes' => true,
																																'status'    => 'success',
																																'message'   => 'Payment verified.',
																																'redirect'  => $this->get_return_url($order),
																															));
																														} elseif ('Failed' === $status) {
																															$msg = isset($data->message) ? $data->message : 'Payment declined by gateway.';
																															$order->update_status('failed', sprintf(__('Payment was declined by Cepta. Details: %s', 'woo-cepta'), $msg));
																															wp_send_json(array('statusRes' => false, 'status' => 'error', 'message' => 'Payment failed or declined.'));
																														} else {
																															$order->add_order_note(sprintf(__('Payment status is currently: %s', 'woo-cepta'), $status));
																															wp_send_json(array('statusRes' => false, 'status' => 'pending', 'message' => 'Payment status is still pending: ' . $status));
																														}
																													} else {
																														$msg = isset($data->message) ? $data->message : 'API verification failed.';
																														$order->update_status('failed', sprintf(__('Transaction verification failed. API Code %d. Error: %s', 'woo-cepta'), $code, $msg));
																														wp_send_json(array(
																															'statusRes'          => false,
																															'status'             => 'error',
																															'message'            => 'Verification failed: ' . $msg,
																															'debug_raw_response' => $body,
																														));
																													}
																												}

																												// --- Card save / subscriptions ---

																												public function save_card_details($cepta_response, $user_id, $order_id)
																												{
																													$this->save_subscription_payment_token($order_id, $cepta_response);

																													$order     = wc_get_order($order_id);
																													$save_card = $order->get_meta('_wc_cepta_save_card');

																													if (
																														$user_id && $this->saved_cards && $save_card &&
																														isset($cepta_response->data->authorization) &&
																														! empty($cepta_response->data->authorization->reusable) &&
																														'card' === $cepta_response->data->authorization->channel
																													) {
																														$auth  = $cepta_response->data->authorization;
																														$cust  = $cepta_response->data->customer;
																														$token = new WC_Payment_Token_CC();

																														$payment_token = $auth->authorization_code . '###' . $cust->email;

																														$token->set_token($payment_token);
																														$token->set_gateway_id($order->get_payment_method());
																														$token->set_card_type(strtolower($auth->card_type));
																														$token->set_last4($auth->last4);
																														$token->set_expiry_month($auth->exp_month);
																														$token->set_expiry_year($auth->exp_year);
																														$token->set_user_id($user_id);
																														$token->save();

																														$order->delete_meta_data('_wc_cepta_save_card');
																														$order->save();
																													}
																												}

																												public function save_subscription_payment_token($order_id, $cepta_response)
																												{
																													if (! function_exists('wcs_order_contains_subscription')) {
																														return;
																													}
																													if (
																														$this->order_contains_subscription($order_id) &&
																														isset($cepta_response->data->authorization) &&
																														! empty($cepta_response->data->authorization->reusable) &&
																														'card' === $cepta_response->data->authorization->channel
																													) {
																														$auth_code      = $cepta_response->data->authorization->authorization_code;
																														$customer_email = $cepta_response->data->customer->email;
																														$payment_token  = $auth_code . '###' . $customer_email;

																														if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) {
																															$subscriptions = wcs_get_subscriptions_for_order($order_id);
																														} elseif (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order_id)) {
																															$subscriptions = wcs_get_subscriptions_for_renewal_order($order_id);
																														} else {
																															$subscriptions = array();
																														}

																														foreach ($subscriptions as $subscription) {
																															$subscription->update_meta_data('_cepta_token', $payment_token);
																															$subscription->save();
																														}
																													}
																												}

																												// --- Helpers ---

																												public function get_custom_fields($order_id)
																												{
																													$order         = wc_get_order($order_id);
																													$custom_fields = array(
																														array('display_name' => 'Plugin', 'variable_name' => 'plugin', 'value' => 'woo-cepta'),
																													);

																													if ($this->custom_metadata) {
																														if ($this->meta_order_id) {
																															$custom_fields[] = array('display_name' => 'Order ID', 'variable_name' => 'order_id', 'value' => $order_id);
																														}
																														if ($this->meta_name) {
																															$custom_fields[] = array('display_name' => 'Customer Name', 'variable_name' => 'customer_name', 'value' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
																														}
																														if ($this->meta_email) {
																															$custom_fields[] = array('display_name' => 'Customer Email', 'variable_name' => 'customer_email', 'value' => $order->get_billing_email());
																														}
																														if ($this->meta_phone) {
																															$custom_fields[] = array('display_name' => 'Customer Phone', 'variable_name' => 'customer_phone', 'value' => $order->get_billing_phone());
																														}
																														if ($this->meta_products) {
																															$items    = $order->get_items();
																															$products = array();
																															foreach ($items as $item) {
																																$products[] = sprintf('%s (Qty: %d)', $item['name'], $item['qty']);
																															}
																															$custom_fields[] = array('display_name' => 'Products', 'variable_name' => 'products', 'value' => implode(' | ', $products));
																														}
																														if ($this->meta_billing_address) {
																															$billing = esc_html(preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_billing_address()));
																															$custom_fields[] = array('display_name' => 'Billing Address', 'variable_name' => 'billing_address', 'value' => $billing);
																														}
																														if ($this->meta_shipping_address) {
																															$shipping = esc_html(preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_shipping_address()));
																															if (empty($shipping)) {
																																$shipping = esc_html(preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_billing_address()));
																															}
																															$custom_fields[] = array('display_name' => 'Shipping Address', 'variable_name' => 'shipping_address', 'value' => $shipping);
																														}
																													}

																													return $custom_fields;
																												}

																												public function is_wc_lt($version)
																												{
																													return version_compare(WC_VERSION, $version, '<');
																												}

																												protected function is_autocomplete_order_enabled($order)
																												{
																													$settings = get_option('woocommerce_' . $order->get_payment_method() . '_settings');
																													return isset($settings['autocomplete_order']) && 'yes' === $settings['autocomplete_order'];
																												}

																												protected function get_gateway_payment_channels($order)
																												{
																													$method = $order->get_payment_method();
																													if ('cepta' === $method) {
																														return array();
																													}
																													$channels = isset($this->payment_channels) ? $this->payment_channels : array();
																													return empty($channels) ? array('card') : $channels;
																												}

																												private function get_cepta_transaction($cepta_txn_ref)
																												{

																													$base  = 'https://adapter.cepta.co/api/v1/pay/verify-payment';
																													$url   = rtrim($base, '/') . '/' . ltrim($cepta_txn_ref, '/');

																													$is_test = $this->testmode ?? true;
																													$pub     = $is_test ? $this->test_public_key : $this->live_public_key;
																													$ts      = time();

																													$headers = array(
																														'Accept'       => 'application/json',
																														'X-Access-Key' => $pub,
																														'X-Access-Ts'  => $ts,
																														'Cache-Control' => 'no-cache',
																													);
																													$args    = array('method' => 'GET', 'headers' => $headers, 'timeout' => 60);
																													$req     = wp_remote_get($url, $args);

																													if (! is_wp_error($req) && 200 === (int) wp_remote_retrieve_response_code($req)) {
																														return json_decode(wp_remote_retrieve_body($req));
																													}
																													return false;
																												}

																												public function get_logo_url()
																												{
																													$country = wc_get_base_location()['country'];
																													if ('GH' === $country) {
																														$url = WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-gh.png', WC_CEPTA_MAIN_FILE));
																													} elseif ('ZA' === $country) {
																														$url = WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-za.png', WC_CEPTA_MAIN_FILE));
																													} elseif ('KE' === $country) {
																														$url = WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-ke.png', WC_CEPTA_MAIN_FILE));
																													} else {
																														$url = WC_HTTPS::force_https_url(plugins_url('assets/images/cepta-wc.png', WC_CEPTA_MAIN_FILE));
																													}
																													return apply_filters('wc_cepta_gateway_icon_url', $url, $this->id);
																												}

																												public function add_payment_method()
																												{
																													wc_add_notice(__('You can only add a new card when placing an order.', 'woo-cepta'), 'error');
																													return;
																												}

																												public function order_contains_subscription($order_id)
																												{
																													return function_exists('wcs_order_contains_subscription') && (wcs_order_contains_subscription($order_id) || wcs_order_contains_renewal($order_id));
																												}

																												// Placeholders for referenced but not defined methods (kept signatures for compatibility).
																												public function process_webhooks() {}
																												public function cepta_wc_payment_popup_action() {}
																												public function verify_cepta_wc_transaction() {}
																											}
