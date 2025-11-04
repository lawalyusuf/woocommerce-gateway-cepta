/**
 * CeptaPay WooCommerce Frontend
 * Requires: jQuery, window.CeptaPay, wc_cepta_params (injected by PHP)
 */

jQuery(function ($) {
  // --- Config ---
  const POLL_DELAY_MS = 30000; // first poll wait
  const POLL_INTERVAL_MS = 3000; // poll cadence
  const ALLOWED_ORIGINS = (wc_cepta_params.allowed_origins || "")
    .split(",")
    .map((s) => s.trim())
    .filter(Boolean);
  const ENFORCE_ORIGINS = ALLOWED_ORIGINS.length > 0;

  // --- State ---
  let delayTimeoutId = null;
  let pollIntervalId = null;
  let pollStarted = false;
  let userClosed = false;
  let callbackFired = false;
  let activeRef = null;
  let initFired = false;

  // --- UI helpers: spinner overlay + modal ---
  function showLoading() {
    if ($("#loading-spinner").length) return;
    $("body").append(
      "<style>.spinner-overlay{position:fixed;inset:0;background:rgba(255,255,255,.8);display:flex;justify-content:center;align-items:center;z-index:9999}.spinner{border:4px solid rgba(0,0,0,.1);border-top:4px solid #3498db;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}</style>" +
        '<div id="loading-spinner" class="spinner-overlay"><div class="spinner"></div></div>'
    );
    $("#wc-cepta-form").hide();
  }
  function hideLoading() {
    $("#loading-spinner").remove();
  }
  function modal(message, redirectUrl) {
    $(".modal-overlay").remove();
    const html =
      "<style>.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;justify-content:center;align-items:center;z-index:10000}.cepta-modal{background:#fff;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,.3);max-width:420px;width:92%}.modal-content{padding:20px;text-align:center}.modal-footer{padding:10px 20px 20px;display:flex;justify-content:flex-end}.btn-primary{background:#3498db;color:#fff;border:none;padding:10px 20px;border-radius:4px;cursor:pointer}</style>" +
      `<div class="modal-overlay"><div class="cepta-modal"><div class="modal-content"><p>${message}</p></div><div class="modal-footer"><button type="button" class="btn btn-primary" id="okButton">OK</button></div></div></div>`;
    $("body").append(html);
    $("#okButton").on("click", () => {
      window.location.href = redirectUrl;
    });
  }

  // --- Pay Now button loading state ---
  function $payBtn() {
    return $("#cepta-payment-button");
  }

  function setButtonLoading() {
    const $btn = $payBtn();
    if (!$btn.length) return;

    if (!$btn.data("orig-text")) {
      $btn.data("orig-text", $.trim($btn.text()));
    }

    if (!$btn.data("locked-width")) {
      const w = $btn.outerWidth();
      $btn.css("width", w + "px");
      $btn.data("locked-width", true);
    }

    if (!$btn.data("orig-pointer-events")) {
      $btn.data("orig-pointer-events", $btn.css("pointer-events") || "");
    }
    $btn.css("pointer-events", "none");

    $btn.text("WAIT…");
  }

  function clearButtonLoading() {
    const $btn = $payBtn();
    if (!$btn.length) return;

    const orig = $btn.data("orig-text");
    $btn.text(orig || "Pay Now");

    // Release pointer events
    const origPE = $btn.data("orig-pointer-events");
    $btn.css("pointer-events", origPE || "");
    $btn.removeData("orig-pointer-events");

    if ($btn.data("locked-width")) {
      $btn.css("width", "");
      $btn.removeData("locked-width");
    }
  }

  // --- Timers ---
  function stopDelay() {
    if (delayTimeoutId) {
      clearTimeout(delayTimeoutId);
      delayTimeoutId = null;
    }
  }
  function stopPolling() {
    if (pollIntervalId) {
      clearInterval(pollIntervalId);
      pollIntervalId = null;
    }
    stopDelay();
    pollStarted = false;
  }

  // --- Messaging helpers ---
  function postTarget() {
    if (window.parent && window.parent !== window) return window.parent;
    if (window.opener && !window.opener.closed) return window.opener;
    return window.top || window;
  }
  function broadcast(eventType, ref) {
    const payload = { event: eventType, transactionRef: ref || activeRef };
    const tgt = postTarget();
    try {
      tgt.postMessage(payload, "*");
      tgt.postMessage(JSON.stringify(payload), "*");
    } catch {}
  }
  function closeSdk(reason) {
    try {
      if (window.CeptaPay?.closeModal)
        window.CeptaPay.closeModal(reason || "close");
      console.warn("[CeptaPay] CLOSE IFRAME MODAL.");
    } catch {}
  }
  function fireOnce(eventType, ref) {
    if (callbackFired) return;
    callbackFired = true;
    stopPolling();
    closeSdk(eventType);
    if (
      eventType === "success" ||
      eventType === "failed" ||
      eventType === "close"
    )
      broadcast(eventType, ref);
    else broadcast(eventType, ref);
  }

  // --- Verify (server) ---
  function verifyTransaction(transactionRef) {
    const ref = transactionRef || activeRef;
    if (!ref)
      return console.warn("[CeptaPay] verifyTransaction called without ref.");

    showLoading();
    const endpoint = "/wc-api/wc_gateway_cepta_popup"; // server handler

    $.ajax({
      url: endpoint,
      method: "POST",
      dataType: "json",
      data: {
        transactionRef: ref,
        // Keep typo to match backend param name:
        ceptaOderId: wc_cepta_params.meta_order_id,
        wc_cepta_payment_nonce: wc_cepta_params.nonce,
      },
      success: function (res) {
        const redirect =
          res.statusRes === true && res.redirect
            ? res.redirect
            : wc_cepta_params.checkout_url || window.location.href;
        window.location.href = redirect;
      },
      error: function (xhr, status, err) {
        console.error("Verification AJAX Error:", status, err);
        modal(
          "An unexpected error occurred during verification. Please contact support.",
          wc_cepta_params.checkout_url || window.location.href
        );
      },
      complete: hideLoading,
    });
  }

  // --- Restore WC buttons  ---
  function restorePayNow() {
    $("#wc-cepta-form").show();
    $("form#payment-form, form#order_review")
      .find("input.cepta_txnref")
      .val("");
    $("#cepta-payment-button")
      .off("click")
      .on("click", function (e) {
        e.preventDefault();
        setButtonLoading();
        handlePayment({ fromClick: true });
      });
    $("form.checkout, form#order_review")
      .find('button, input[type="submit"]')
      .prop("disabled", true)
      .show();
  }

  // --- Polling lifecycle ---
  function startPolling(transactionRef) {
    if (!transactionRef || pollStarted) return;
    if (!window.CeptaPay?.confirmStatus)
      return console.warn(
        "[CeptaPay] confirmStatus not available; skipping poll."
      );

    userClosed = false;
    callbackFired = false;
    pollStarted = true;
    activeRef = transactionRef;

    delayTimeoutId = setTimeout(function () {
      if (userClosed) {
        stopPolling();
        return;
      }

      pollIntervalId = setInterval(async function () {
        if (userClosed) {
          stopPolling();
          return;
        }

        try {
          const result = await window.CeptaPay.confirmStatus(transactionRef);
          if (!result?.status) return;
          const d = result.data;
          if (!d) return;

          const txnRef = d.transactionReference;
          const amount = parseFloat(d.amount || 0);
          if (!txnRef || !(amount > 0)) return;

          const status = d.status;
          if (status === "Successful") {
            fireOnce("success", transactionRef);
          } else if (status === "Failed") {
            fireOnce("failed", transactionRef);
          }
        } catch (err) {
          console.error("[CeptaPay] Polling error:", err);
          $("#wc-cepta-form").show();
          fireOnce("close", transactionRef);
        }
      }, POLL_INTERVAL_MS);
    }, POLL_DELAY_MS);
  }

  // --- SDK callbacks ---
  function onSuccess(ref) {
    clearButtonLoading();
    verifyTransaction(ref);
  }
  function onFailed(ref) {
    console.error("Error during payment processing:", ref);
    clearButtonLoading();
    verifyTransaction(ref);
  }
  function onClose(ref) {
    userClosed = true;
    clearButtonLoading();
    try {
      window.CeptaPay?.closeModal();
    } catch {}
  }

  // --- URL ?ref= passthrough ---
  function getUrlRef() {
    const sp = new URLSearchParams(window.location.search);
    if (sp.get("ref")) return sp.get("ref");
    const idx = window.location.href.lastIndexOf("?");
    if (idx === -1) return null;
    for (const pair of window.location.href.substring(idx + 1).split("&")) {
      const [k, v] = pair.split("=");
      if (k === "ref") return v;
    }
    return null;
  }

  // --- Payment entry ---
  async function handlePayment(opts = { fromClick: false }) {
    $("#wc-cepta-form").show();
    $("form#payment-form, form#order_review")
      .find("input.cepta_txnref")
      .val("");
    const amount = Number(wc_cepta_params.amount);
    const ref = "WC_" + Date.now();
    activeRef = ref;

    // Parent-verification path if URL already has ?ref=
    const urlRef = getUrlRef();
    if (urlRef) {
      clearButtonLoading();
      broadcast("success", urlRef);
      return;
    }

    if (!window.CeptaPay?.checkout) {
      console.error("CeptaPay SDK not loaded.");
      clearButtonLoading();
      onFailed(ref);
      return;
    }

    const paymentData = {
      amount,
      currency: wc_cepta_params.currency,
      description: "Payment for Order ID " + wc_cepta_params.meta_order_id,
      pageName: "",
      customerEmail: wc_cepta_params.email,
      transactionReference: ref,
      customUrlText: "",
      callbackUrl:
        window.location.href +
        "&nonce=" +
        encodeURIComponent(wc_cepta_params.nonce),
      isPlugin: true,
    };

    const config = {
      publicKey: wc_cepta_params.public_key,
      secretKey: wc_cepta_params.secret_key,
      baseUrl: wc_cepta_params.base_url,
    };

    try {
      await window.CeptaPay.checkout({
        paymentData,
        config,
        onSuccess,
        onFailed,
        onClose,
      });

      clearButtonLoading();

      startPolling(ref);
    } catch (err) {
      console.error("CeptaPay Checkout failed to launch:", err?.message);
      clearButtonLoading();
      onFailed(ref);
    }
  }

  // --- postMessage bridge (safe origin) ---
  window.addEventListener(
    "message",
    function (event) {
      if (ENFORCE_ORIGINS && !ALLOWED_ORIGINS.includes(event.origin)) return;

      let payload = event.data;
      if (typeof payload === "string") {
        try {
          payload = JSON.parse(payload);
        } catch {
          return;
        }
      }
      if (!payload?.event) return;

      const ref = payload.transactionRef || activeRef;
      switch (payload.event) {
        case "success":
          onSuccess(ref);
          break;
        case "failed":
          onFailed(ref);
          break;
        case "close":
          onClose(ref);
          break;
        default:
          break;
      }
    },
    false
  );

  window.addEventListener("beforeunload", stopPolling);

  // --- WooCommerce bindings ---
  $("form.checkout").on("checkout_place_order_cepta", function (e) {
    e.preventDefault();
    handlePayment({ fromClick: false });
    return false;
  });

  $("#order_review").on("submit", function (e) {
    if ($("#payment_method_cepta").is(":checked")) {
      e.preventDefault();
      handlePayment({ fromClick: false });
      return false;
    }
  });

  $("#cepta-payment-button").on("click", function (e) {
    e.preventDefault();
    setButtonLoading();
    handlePayment({ fromClick: true });
    return false;
  });

  // --- Init ---
  $("#wc-cepta-form").hide();
  if (!initFired) {
    initFired = true;
    handlePayment({ fromClick: false });
  }
  if (String(wc_cepta_params.is_order_pay_page) === "true" && !initFired) {
    initFired = true;
    handlePayment({ fromClick: false });
  }

  restorePayNow();
});
