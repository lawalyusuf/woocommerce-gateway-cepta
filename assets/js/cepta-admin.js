/*!
 * CeptaPay – WooCommerce Admin UI helpers
 * - Toggles settings (test keys, split payment, metadata, channel filters)
 * - Select2 icon rendering and secret-key show/hide
 * - Keeps original behavior, tightened selectors & checks
 */
jQuery(function ($) {
  "use strict";

  const Admin = {
    init() {
      // Test mode: toggle which secret key row shows.
      $(document.body).on(
        "change",
        "#woocommerce_cepta_testmode",
        Admin.toggleKeys
      );
      $("#woocommerce_cepta_testmode").trigger("change");

      // Split payment: toggle relevant rows.
      $(document.body).on(
        "change",
        ".woocommerce_cepta_split_payment, #woocommerce_cepta_split_payment",
        Admin.toggleSplitPayment
      );
      $("#woocommerce_cepta_split_payment").trigger("change");

      // Custom metadata: toggle all metadata rows.
      $(".wc-cepta-metadata")
        .on("change", Admin.toggleMetadata)
        .trigger("change");

      // Channel filters: show card/bank allowlists only when card channel is selected.
      $(".wc-cepta-payment-channels")
        .on("change", Admin.toggleChannelFilters)
        .trigger("change");

      // Select2 rendering for payment icons (safe if select2 exists).
      if ($.fn.select2) {
        $(".wc-cepta-payment-icons").select2({
          templateResult: Admin.renderIconOption,
          templateSelection: Admin.renderIconSelection,
          width: "resolve",
        });
      }

      // Secret key reveal/hide buttons (append once).
      Admin.addSecretToggle(
        "#woocommerce_cepta_test_secret_key, #woocommerce_cepta_live_secret_key"
      );
      $(document.body).on(
        "click",
        ".wc-cepta-toggle-secret",
        Admin.toggleSecretField
      );
    },

    // --- Handlers ---
    toggleKeys() {
      const isTest = $(this).is(":checked");
      const testRow = $("#woocommerce_cepta_test_secret_key").closest("tr");
      const liveRow = $("#woocommerce_cepta_live_secret_key").closest("tr");
      testRow.toggle(!!isTest);
      liveRow.toggle(!isTest);
    },

    toggleSplitPayment() {
      const checked = $(this).is(":checked");
      const $subacct = $(".woocommerce_cepta_subaccount_code").closest("tr");
      const $chargeAcct = $(
        ".woocommerce_cepta_split_payment_charge_account"
      ).closest("tr");
      const $txnCharge = $(
        ".woocommerce_cepta_split_payment_transaction_charge"
      ).closest("tr");
      $subacct.toggle(checked);
      $chargeAcct.toggle(checked);
      $txnCharge.toggle(checked);
    },

    toggleMetadata() {
      const sel =
        ".wc-cepta-meta-order-id, .wc-cepta-meta-name, .wc-cepta-meta-email, .wc-cepta-meta-phone, .wc-cepta-meta-billing-address, .wc-cepta-meta-shipping-address, .wc-cepta-meta-products";
      $(sel).closest("tr").toggle($(this).is(":checked"));
    },

    toggleChannelFilters() {
      const channels = $(".wc-cepta-payment-channels").val() || [];
      const hasCard = channels.indexOf("card") !== -1;
      $(".wc-cepta-cards-allowed").closest("tr").toggle(hasCard);
      $(".wc-cepta-banks-allowed").closest("tr").toggle(hasCard);
    },

    addSecretToggle(selector) {
      $(selector).each(function () {
        const $input = $(this);
        // Avoid duplicate buttons if re-initialized.
        if ($input.next(".wc-cepta-toggle-secret").length) return;
        $input.after(
          '<button type="button" class="wc-cepta-toggle-secret button-secondary" style="height:30px;margin-left:6px;">' +
            '<span class="dashicons dashicons-visibility" aria-hidden="true"></span>' +
            "</button>"
        );
      });
    },

    toggleSecretField(e) {
      e.preventDefault();
      const $btn = $(this);
      const $icon = $btn.find(".dashicons");
      const $input = $btn
        .closest("tr")
        .find("input.input-text, input[type='password'], input[type='text']")
        .first();
      const type = ($input.attr("type") || "").toLowerCase();
      const toType = type === "text" ? "password" : "text";

      $input.attr("type", toType);
      $icon.toggleClass("dashicons-visibility dashicons-hidden");
    },

    // --- Select2 templates ---
    renderIconOption(option) {
      if (!option.id) return option.text;
      const safeVal = String(option.element.value || "")
        .toLowerCase()
        .replace(/[^a-z0-9_\-]/g, "");
      const src =
        (window.wc_cepta_admin_params?.plugin_url || "") +
        "/assets/images/" +
        safeVal +
        ".png";
      return $(
        '<span><img src="' +
          src +
          '" alt="" style="height:15px;width:18px;object-fit:contain;margin-right:6px;" />' +
          Admin.escape(option.text) +
          "</span>"
      );
    },

    renderIconSelection(option) {
      return option.text;
    },

    // --- Utils ---
    escape(s) {
      return String(s).replace(
        /[&<>"']/g,
        (c) =>
          ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;",
          }[c])
      );
    },
  };

  Admin.init();
});
