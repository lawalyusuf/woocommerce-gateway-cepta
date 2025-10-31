== Cepta Payment Gateway for WooCommerce ==
Contributors: lawalyusuf
Donate link: https://cepta.co
Tags: cepta, woocommerce, payment gateway, payments, cards, visa, mastermastercard
Requires Plugins: woocommerce
Requires at least: 6.2
Requires PHP: 7.4+
Requires at least: 6.2
Tested up to: 10.3.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept cards and account transfers securely with Cepta — for seamless, fast, and reliable order processing.

== Description ==
Cepta WooCommerce Payment Gateway provides secure, seamless card and account transfer processing for quick and efficient transactions.

== Introduction ==
This document outlines the scope of work for developing plugins that integrate the Cepta Payment Gateway API into a merchant’s website or product.

The project involves creating WooCommerce plugins to enable seamless integration of Cepta’s Payment Gateway API, ensuring smooth payment processing within the merchant’s platform.

Cepta WooCommerce Payment Gateway enables secure and seamless card and account transfer payments, ensuring fast and efficient order processing.

With Cepta for WooCommerce, you can securely accept payments via:

* Credit/Debit Cards — Accept Visa and Mastercard.

* Localized Payment Options — Enable local payment methods specific to your operating regions:

* Bank Transfer 

== Why Choose Cepta? ==

* Quick Integration: Go from installation to accepting your first transaction in minutes with our guided setup wizard.

* Transparent and Competitive Pricing

* Optimized Conversion: Utilize the modern, embedded Cepta Checkout experience designed for speed and minimizing checkout abandonment.

* Advanced Risk Management: Cepta's proprietary fraud detection engine actively screens transactions to protect your business and reduce chargebacks.

* Powerful Merchant Dashboard: Get a clear, elegant overview of your sales, customer data, and settlement history in a centralized dashboard.

* Dedicated Support: Access a responsive and knowledgeable merchant support team, available 24/7 via chat and email.

* Developer Friendly: Clearly documented SDKs and APIs empower developers to build fully customized payment flows tailored to their business needs.

== Getting Started ==

1.	Installation process

    1.  Download the plugin zip file
    3.  Click on the "Upload" option, then click "Choose File" to select the zip file from your computer. Once selected, press "OK" and press the "Install Now" button.
    4.  Activate the plugin.
    5.  Open the settings page for WooCommerce and click the "Payment" tab.
    6.  Configure your Cepta Payment Gateway settings. See below for details.

        Configure the plugin:

        To configure the plugin, go to WooCommerce > Settings from the left hand menu, then click Checkout from the top tab. You will see Cepta Payment Gateway as part of the available Checkout Options. Click on it to Manage to configure the Hudrogen payment gateway.

        * __Enable/Disable__ - check the box to enable Cepta Payment Gateway.
        * __Title__ - allows you to determine what your customers will see this payment option as on the checkout page.
        * __Description__ - controls the message that appears under the payment fields on the checkout page. Here you can list the types of cards you accept.
        * __Test Mode__ - Check to enable test mode. Test mode enables you to test payments before going live. If you ready to start receving real payment on your site, kindly uncheck this.
        * __Sandbox__ - Enter your sandbox Keys here. Get your API Keys from your Cepta account under Settings > [API Credentials]([url](https://app.cepta.co/))
        * __Live Keys__ - Enter your Live Keys here. Get your Authorization token from your Cepta account under Settings 
        * __Payment Option__ - Inline Checkout lets customers pay directly on your site, while Redirect takes them to Cepta to complete payment.
        * Click on Save Changes for the changes you made to be effected.

    If Cepta is not available in your payment method options, please check the following settings in your WooCommerce and plugin configuration:
    
    *   You've checked the __"Enable/Disable"__ checkbox
    *   You've entered your __API Keys__ in the appropriate field
    *   You've clicked on __Save Changes__ during setup

2.	Software dependencies

    1. You need to have WooCommerce plugin installed and activated on your WordPress site.
    2. You need to open a Cepta merchant account on Cepta
    3. works with WooCommerce v8.0 and above

3.	Latest releases

    1. 1.0.0 - October 20, 2025.


4.	API references

    * https://docs.cepta.co/api-reference/introduction

    * https://cepta.co/


== Contribute ==

Explain how other users and developers can contribute to make your code better. 

If you discover a bug or have a solution to improve the Cepta Payment Gateway for the WooCommerce plugin,
we welcome your contributions to enhance the code.

 * Visit our GitHub repository: [https://github.com/lawalyusuf/woocommerce-gateway-cepta]

 * Create a detailed bug report or feature request in the "Issues" section.

 * If you have a code improvement or bug fix, feel free to submit a pull request.

        * Fork the repository on GitHub

        * Clone the repository into your local system and create a branch that describes what you are working on by pre-fixing with feature-name.

        * Make the changes to your forked repository's branch. Ensure you are using PHP Coding Standards (PHPCS).

        * Make commits that are descriptive and breaks down the process for better understanding.

        * Push your fix to the remote version of your branch and create a PR that aims to merge that branch into master.
        
        * After you follow the step above, the next stage will be waiting on us to merge your Pull Request.

 Your contributions help us make the PG plugin even better for the community. Thank you!

== Screenshots ==

1. Cepta Payment Gateway for WooCommerce Setting Page

2. Cepta Payment Gateway for WooCommer checkout page

3. Cepta Inline Checkout payment page

