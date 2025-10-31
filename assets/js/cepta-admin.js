jQuery(function ($) {
  "use strict";

  /**
   * Object to handle cepta admin functions.
   */
  var wc_cepta_admin = {
    /**
     * Initialize.
     */
    init: function () {
      // Toggle api key settings.
      $(document.body).on("change", "#woocommerce_cepta_testmode", function () {
        var test_secret_key = $("#woocommerce_cepta_test_secret_key")
            .parents("tr")
            .eq(0),
          live_secret_key = $("#woocommerce_cepta_live_secret_key")
            .parents("tr")
            .eq(0);

        if ($(this).is(":checked")) {
          test_secret_key.show();
          live_secret_key.hide();
        } else {
          test_secret_key.hide();
          live_secret_key.show();
        }
      });

      $("#woocommerce_cepta_testmode").change();

      $(document.body).on(
        "change",
        ".woocommerce_cepta_split_payment",
        function () {
          var subaccount_code = $(".woocommerce_cepta_subaccount_code")
              .parents("tr")
              .eq(0),
            subaccount_charge = $(
              ".woocommerce_cepta_split_payment_charge_account"
            )
              .parents("tr")
              .eq(0),
            transaction_charge = $(
              ".woocommerce_cepta_split_payment_transaction_charge"
            )
              .parents("tr")
              .eq(0);

          if ($(this).is(":checked")) {
            subaccount_code.show();
            subaccount_charge.show();
            transaction_charge.show();
          } else {
            subaccount_code.hide();
            subaccount_charge.hide();
            transaction_charge.hide();
          }
        }
      );

      $("#woocommerce_cepta_split_payment").change();

      // Toggle Custom Metadata settings.
      $(".wc-cepta-metadata")
        .change(function () {
          if ($(this).is(":checked")) {
            $(
              ".wc-cepta-meta-order-id, .wc-cepta-meta-name, .wc-cepta-meta-email, .wc-cepta-meta-phone, .wc-cepta-meta-billing-address, .wc-cepta-meta-shipping-address, .wc-cepta-meta-products"
            )
              .closest("tr")
              .show();
          } else {
            $(
              ".wc-cepta-meta-order-id, .wc-cepta-meta-name, .wc-cepta-meta-email, .wc-cepta-meta-phone, .wc-cepta-meta-billing-address, .wc-cepta-meta-shipping-address, .wc-cepta-meta-products"
            )
              .closest("tr")
              .hide();
          }
        })
        .change();

      // Toggle Bank filters settings.
      $(".wc-cepta-payment-channels")
        .on("change", function () {
          var channels = $(".wc-cepta-payment-channels").val();

          if ($.inArray("card", channels) != "-1") {
            $(".wc-cepta-cards-allowed").closest("tr").show();
            $(".wc-cepta-banks-allowed").closest("tr").show();
          } else {
            $(".wc-cepta-cards-allowed").closest("tr").hide();
            $(".wc-cepta-banks-allowed").closest("tr").hide();
          }
        })
        .change();

      $(".wc-cepta-payment-icons").select2({
        templateResult: formatCeptaPaymentIcons,
        templateSelection: formatCeptaPaymentIconDisplay,
      });

      $(
        "#woocommerce_cepta_test_secret_key, #woocommerce_cepta_live_secret_key"
      ).after(
        '<button class="wc-cepta-toggle-secret" style="height: 30px; margin-left: 2px; cursor: pointer"><span class="dashicons dashicons-visibility"></span></button>'
      );

      $(".wc-cepta-toggle-secret").on("click", function (event) {
        event.preventDefault();

        let $dashicon = $(this).closest("button").find(".dashicons");
        let $input = $(this).closest("tr").find(".input-text");
        let inputType = $input.attr("type");

        if ("text" == inputType) {
          $input.attr("type", "password");
          $dashicon.removeClass("dashicons-hidden");
          $dashicon.addClass("dashicons-visibility");
        } else {
          $input.attr("type", "text");
          $dashicon.removeClass("dashicons-visibility");
          $dashicon.addClass("dashicons-hidden");
        }
      });
    },
  };

  function formatCeptaPaymentIcons(payment_method) {
    if (!payment_method.id) {
      return payment_method.text;
    }

    var $payment_method = $(
      '<span><img src=" ' +
        wc_cepta_admin_params.plugin_url +
        "/assets/images/" +
        payment_method.element.value.toLowerCase() +
        '.png" class="img-flag" style="height: 15px; weight:18px;" /> ' +
        payment_method.text +
        "</span>"
    );

    return $payment_method;
  }

  function formatCeptaPaymentIconDisplay(payment_method) {
    return payment_method.text;
  }

  wc_cepta_admin.init();
});
