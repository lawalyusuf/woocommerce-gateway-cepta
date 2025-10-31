/**
 * WooCommerce CeptaPay Payment Gateway Client Script
 */

jQuery(function ($) {
  /**
   * Prepares an array of metadata objects for the payment gateway.
   * @returns {Array<object>} An array of meta objects.
   */

  function prepareMetadata() {
    let metadata = [
      {
        display_name: "Plugin",
        variable_name: "plugin",
        value: "woo-cepta",
      },
    ];

    wc_cepta_params.meta_order_id &&
      metadata.push({
        display_name: "Order ID",
        variable_name: "order_id",
        value: wc_cepta_params.meta_order_id,
      });
    wc_cepta_params.meta_name &&
      metadata.push({
        display_name: "Customer Name",
        variable_name: "customer_name",
        value: wc_cepta_params.meta_name,
      });
    wc_cepta_params.meta_email &&
      metadata.push({
        display_name: "Customer Email",
        variable_name: "customer_email",
        value: wc_cepta_params.meta_email,
      });
    wc_cepta_params.meta_phone &&
      metadata.push({
        display_name: "Customer Phone",
        variable_name: "customer_phone",
        value: wc_cepta_params.meta_phone,
      });
    wc_cepta_params.meta_billing_address &&
      metadata.push({
        display_name: "Billing Address",
        variable_name: "billing_address",
        value: wc_cepta_params.meta_billing_address,
      });
    wc_cepta_params.meta_shipping_address &&
      metadata.push({
        display_name: "Shipping Address",
        variable_name: "shipping_address",
        value: wc_cepta_params.meta_shipping_address,
      });
    wc_cepta_params.meta_products &&
      metadata.push({
        display_name: "Products",
        variable_name: "products",
        value: wc_cepta_params.meta_products,
      });

    return metadata;
  }

  /**
   * Retrieves custom filters (banks and card brands).
   * @returns {object} An object containing 'banks' and 'card_brands' if enabled.
   */
  function getCustomFilters() {
    let filters = {};
    if (wc_cepta_params.card_channel) {
      if (wc_cepta_params.banks_allowed) {
        filters.banks = wc_cepta_params.banks_allowed;
      }
      if (wc_cepta_params.cards_allowed) {
        filters.card_brands = wc_cepta_params.cards_allowed;
      }
    }
    return filters;
  }

  /**
   * Determines the payment channels to be enabled.
   * @returns {Array<string>} An array of enabled channel names.
   */
  function getEnabledChannels() {
    let channels = [];
    wc_cepta_params.bank_channel && channels.push("bank");
    wc_cepta_params.card_channel && channels.push("card");
    wc_cepta_params.ussd_channel && channels.push("ussd");
    wc_cepta_params.qr_channel && channels.push("qr");
    wc_cepta_params.bank_transfer_channel && channels.push("bank_transfer");
    return channels;
  }

  /**
   * Displays a custom modal with a message and an OK button that redirects.
   * @param {string} message - The message to display inside the modal.
   * @param {string} redirectUrl - The URL to redirect to when the 'OK' button is clicked.
   */
  function displayModal(message, redirectUrl) {
    // Remove any previous modal
    $(".modal-overlay").remove();

    const modalHtml = `
            <style>
                /* ... (Styles from original code) ... */
                .modal-overlay {
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center;
                    z-index: 10000;
                }
                .cepta-modal-container {
                    background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
                    position: relative; max-width: 400px; width: 90%; z-index: 10001; 
                }
                .modal-content {
                    padding: 20px; text-align: center;
                }
                .modal-footer {
                    padding: 10px 20px 20px;
                    display: flex; justify-content: flex-end;
                }
                .btn-primary {
                    background-color: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;
                }
            </style>
            <div class="modal-overlay">
                <div class="cepta-modal-container">
                    <div class="modal-content">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="okButton">OK</button>
                    </div>
                </div>
            </div>
        `;
    $("body").append(modalHtml);

    // Setup redirect on OK button click
    $("#okButton").on("click", function () {
      window.location.href = redirectUrl;
    });
  }

  /**
   * Displays a temporary loading spinner overlay while verifying status.
   */
  function showVerificationSpinner() {
    if ($("#loading-spinner").length) return; // Prevent duplicates

    const spinnerHtml = `
            <style>
                /* ... (Styles from original code) ... */
                .spinner-overlay {
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(255, 255, 255, 0.8); display: flex; justify-content: center; align-items: center;
                    z-index: 9999;
                }
                .spinner {
                    border: 4px solid rgba(0, 0, 0, 0.1); border-top: 4px solid #3498db;
                    border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
            <div id="loading-spinner" class="spinner-overlay">
                <div class="spinner"></div>
            </div>
        `;
    $("body").append(spinnerHtml);
    $("#wc-cepta-form").show();
  }

  /**
   * Removes the loading spinner.
   */
  function removeSpinner() {
    $("#loading-spinner").remove();
  }

  /**
   * Handles the secure AJAX call to the server to verify a payment status.
   * @param {string} transactionRef - The unique reference of the transaction to verify.
   */
  function verifyTransaction(transactionRef) {
    showVerificationSpinner();

    const orderId = wc_cepta_params.meta_order_id;
    const nonce = wc_cepta_params.nonce;
    const verificationUrl = "/wc-api/wc_gateway_cepta_popup"; // Your PHP endpoint

    $.ajax({
      url: verificationUrl,
      method: "POST",
      data: {
        transactionRef: transactionRef,
        ceptaOderId: orderId,
        wc_cepta_payment_nonce: nonce,
      },
      dataType: "json",
      success: function (response) {
        // Determine the redirection URL (likely the success page for the order)
        let redirectUrl = wc_cepta_params.checkout_url; // Use the localized checkout URL or something similar for fallback

        // Construct a better base URL for redirection (if order completion is needed)
        if (wc_cepta_params.hasOwnProperty("return_url")) {
          redirectUrl = wc_cepta_params.return_url;
        } else {
          // Fallback to home/cart if no specific return_url is localized
          redirectUrl = window.location.href.replace(
            /\/checkout\/order-pay\/\d+\/.*/,
            ""
          );
        }

        if (response.statusRes === true) {
          displayModal(
            `Your payment for order #${orderId} is successful and confirmed! Click OK to proceed.`,
            redirectUrl
          );
        } else {
          displayModal(
            `Your payment for order #${orderId} has failed. Please try again.`,
            redirectUrl
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Verification AJAX Error:", status, error);
        displayModal(
          `An unexpected error occurred during verification. Please contact support.`,
          wc_cepta_params.checkout_url || window.location.href
        );
      },
      complete: function () {
        removeSpinner();
      },
    });
  }

  /**
   * initiate payment process.
   */
  async function initCeptaPayment() {
    $("#wc-cepta-form").hide();
    $("form#payment-form, form#order_review")
      .find("input.cepta_txnref")
      .val("");

    const amount = Number(wc_cepta_params.amount);
    const currentUrl = window.location.href;
    const transactionRef = "WC_" + Date.now();

    // Prepare Payment Data for the SDK
    const customFilters = getCustomFilters();
    const paymentData = {
      amount: amount,
      currency: wc_cepta_params.currency,
      description: "Payment for Order ID " + wc_cepta_params.meta_order_id,
      pageName: "",
      customerEmail: wc_cepta_params.email,
      transactionReference: transactionRef,
      customUrlText: "",
      callbackUrl:
        currentUrl + "&nonce=" + encodeURIComponent(wc_cepta_params.nonce),
      isPlugin: true,
    };

    // Used for client-side HMAC generation
    const sdkConfig = {
      publicKey: wc_cepta_params.public_key,
      secretKey: wc_cepta_params.secret_key,
      baseUrl: wc_cepta_params.base_url,
    };

    /**
     * Called when the SDK confirms the payment status is "Successful".
     * @param {string} ref The final transaction reference.
     */
    function handleSuccess(ref) {
      $("form#payment-form, form#order_review")
        .find("input.cepta_txnref")
        .val(ref);

      $("form.checkout, form#order_review").submit();
    }

    /**
     * Called when the SDK confirms the payment status is "Failed" or initiation fails.
     * @param {string | null} ref The transaction reference.
     */
    function handleFailure(ref) {
      console.error("CeptaPay Payment Failed. Ref:", ref);
      $("#wc-cepta-form").show();
    }

    /**
     * Called when the user manually closes the payment modal.
     * @param {string} ref The transaction reference.
     */
    function handleClose(ref) {
      console.warn(
        "CeptaPay Modal Closed by User. Initiating verification (status check)..."
      );
      verifyTransaction(ref);
    }

    let urlRef = new URLSearchParams(window.location.search).get(
      "TransactionRef"
    );

    if (!urlRef) {
      const queryIndex = currentUrl.lastIndexOf("?");
      if (queryIndex !== -1) {
        const params = currentUrl.substring(queryIndex + 1).split("&");
        for (let i = 0; i < params.length; i++) {
          const parts = params[i].split("=");
          if ("TransactionRef" === parts[0]) {
            urlRef = parts[1];
            break;
          }
        }
      }
    }

    if (urlRef) {
      console.log(
        "Found transaction reference in URL. Initiating verification..."
      );
      verifyTransaction(urlRef);
    } else {
      if (
        typeof window.CeptaPay === "undefined" ||
        typeof window.CeptaPay.checkout !== "function"
      ) {
        console.error(
          "CeptaPay SDK is not loaded. Cannot proceed with payment."
        );
        handleFailure(paymentData.transactionReference);
        return;
      }

      try {
        window.CeptaPay.checkout({
          paymentData: paymentData,
          config: sdkConfig,
          onSuccess: handleSuccess,
          onFailed: handleFailure,
          onClose: handleClose,
        });
      } catch (e) {
        console.error("CeptaPay Checkout failed to launch:", e.message);
        handleFailure(paymentData.transactionReference);
      }
    }
  }

  $("form.checkout").on("checkout_place_order_cepta", function (e) {
    e.preventDefault();
    initCeptaPayment();
    return false;
  });

  $("#order_review").on("submit", function (e) {
    if ($("#payment_method_cepta").is(":checked")) {
      e.preventDefault();
      initCeptaPayment();
      return false;
    }
  });

  if ($("#cepta-payment-button").length) {
    $("#cepta-payment-button").on("click", function (e) {
      e.preventDefault();
      initCeptaPayment();
      return false;
    });
  }

  $("#wc-cepta-form").hide();
  initCeptaPayment();

  $("#cepta_form form#order_review").submit(function () {
    handlePayment();
  });

  if (wc_cepta_params.is_order_pay_page === "true") {
    initCeptaPayment();
  }
});
