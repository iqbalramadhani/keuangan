/* ============================================================================
 * categories.js — minimal client-side enhancement:
 *   - focus the input when the page loads
 *   - trim & validate before submit (server-side is still authoritative)
 * ========================================================================== */
(function () {
  'use strict';

  var firstInput = document.querySelector('main input[name="name"]');
  if (firstInput) firstInput.focus();
})();
