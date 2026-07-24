/* ============================================================================
 * app.js — tiny helpers & fetch wrapper.
 * Loaded on every page (defer). Exposes window.App.
 * ========================================================================== */

(function () {
  'use strict';

  /**
   * Read CSRF token from the meta tag the layout injected.
   * (Falls back to looking in the first hidden _csrf input.)
   */
  function getCsrf() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    var input = document.querySelector('input[name="_csrf"]');
    return input ? input.value : '';
  }

  /** tiny selector */
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  /** create element */
  function el(tag, attrs, children) {
    var node = document.createElement(tag);
    attrs = attrs || {};
    Object.keys(attrs).forEach(function (k) {
      if (k === 'class') { node.className = attrs[k]; }
      else if (k === 'text') { node.textContent = attrs[k]; }
      else if (k === 'html') { node.innerHTML = attrs[k]; }
      else if (k.indexOf('data-') === 0) { node.setAttribute(k, attrs[k]); }
      else { node.setAttribute(k, attrs[k]); }
    });
    (children || []).forEach(function (c) { node.appendChild(c); });
    return node;
  }

  /**
   * fetch wrapper: adds CSRF for non-GET, parses JSON, throws on !ok.
   */
  function jsonFetch(url, opts) {
    opts = opts || {};
    opts.headers = opts.headers || {};
    opts.headers['X-Requested-With'] = 'XMLHttpRequest';
    var method = (opts.method || 'GET').toUpperCase();
    if (method !== 'GET') {
      opts.headers['X-CSRF-Token'] = getCsrf();
    }
    if (opts.body && typeof opts.body !== 'string') {
      var fd = new FormData();
      Object.keys(opts.body).forEach(function (k) { fd.append(k, opts.body[k]); });
      opts.body = fd;
      // don't set content-type — browser sets multipart boundary automatically
    }
    return fetch(url, opts).then(function (r) {
      var ct = r.headers.get('content-type') || '';
      var data = ct.indexOf('application/json') >= 0 ? r.json() : r.text();
      return data.then(function (body) {
        if (!r.ok) {
          var err = new Error('HTTP ' + r.status);
          err.status = r.status; err.body = body;
          throw err;
        }
        return body;
      });
    });
  }

  /** Confirm-then-submit helper for delete buttons. */
  function confirmSubmit(msg) {
    return window.confirm(msg || 'Yakin?');
  }

  window.App = {
    csrf: getCsrf,
    $: $, $$: $$, el: el,
    jsonFetch: jsonFetch,
    confirmSubmit: confirmSubmit
  };
})();
