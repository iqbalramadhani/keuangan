/* ============================================================================
 * transactions.js — page-level enhancements:
 *   - format amount input as user types (visual nicety; server re-validates)
 * ========================================================================== */
(function () {
  'use strict';

  // Format visible number with thousand separators on blur (do not edit on focus).
  var amountInput = document.querySelector('input[name="amount"]');
  if (!amountInput) return;

  amountInput.addEventListener('blur', function () {
    var v = amountInput.value.replace(/[^0-9.,]/g, '').replace(',', '.');
    var n = parseFloat(v);
    if (isNaN(n)) return;
    amountInput.value = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(n);
  });

  amountInput.addEventListener('focus', function () {
    // Strip formatting so user can type a fresh number.
    var n = parseFloat(amountInput.value.replace(/[^0-9.,-]/g, '').replace(',', '.'));
    if (!isNaN(n)) amountInput.value = String(n);
  });
})();
