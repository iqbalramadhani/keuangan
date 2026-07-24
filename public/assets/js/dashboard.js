/* ============================================================================
 * dashboard.js — 12-month income vs expense bar chart on <canvas>.
 * No external deps, no CDN. Honors devicePixelRatio for crispness.
 * ========================================================================== */

(function () {
  'use strict';

  var canvas = document.getElementById('chart');
  if (!canvas) return;

  var dataEl = document.getElementById('summary-data');
  if (!dataEl) return;

  /** @type {Array<{label:string, income:number, expense:number, iso:string}>} */
  var data;
  try {
    data = JSON.parse(dataEl.textContent);
  } catch (e) {
    data = [];
  }
  if (!Array.isArray(data) || data.length === 0) return;

  var dpr = window.devicePixelRatio || 1;

  function fmtId(n) {
    try {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0
      }).format(n);
    } catch (_) {
      return 'Rp ' + Math.round(n).toLocaleString('id-ID');
    }
  }

  function setupCanvas() {
    var cssW = canvas.clientWidth || canvas.parentNode.clientWidth || 600;
    var cssH = parseInt(canvas.getAttribute('height'), 10) || 180;
    canvas.width  = Math.max(1, Math.round(cssW * dpr));
    canvas.height = Math.max(1, Math.round(cssH * dpr));
    canvas.style.width  = cssW + 'px';
    canvas.style.height = cssH + 'px';
    return { w: cssW, h: cssH };
  }

  var sz = setupCanvas();
  var ctx = canvas.getContext('2d');
  ctx.scale(dpr, dpr);

  var paddingL = 56, paddingR = 16, paddingT = 14, paddingB = 28;
  var plotW = sz.w - paddingL - paddingR;
  var plotH = sz.h - paddingT - paddingB;

  var income  = data.map(function (r) { return Number(r.income)  || 0; });
  var expense = data.map(function (r) { return Number(r.expense) || 0; });
  var maxVal  = Math.max.apply(null, income.concat(expense).concat([1]));

  // Round max up to a nice round number, with steps.
  function niceMax(v) {
    if (v <= 0) return 1;
    var exp = Math.floor(Math.log10(v));
    var base = Math.pow(10, exp);
    var f = v / base;
    var step;
    if (f <= 1)      step = 1;
    else if (f <= 2) step = 2;
    else if (f <= 5) step = 5;
    else             step = 10;
    return step * base;
  }
  var yMax = niceMax(maxVal);

  // Grid + Y-axis labels.
  ctx.font = '11px -apple-system, "Segoe UI", Roboto, sans-serif';
  ctx.fillStyle = '#6b7280';
  ctx.strokeStyle = '#e5e7eb';
  ctx.textBaseline = 'middle';
  ctx.textAlign = 'right';

  var yTicks = 4;
  for (var t = 0; t <= yTicks; t++) {
    var yy = paddingT + plotH * (t / yTicks);
    var val = yMax * (1 - t / yTicks);
    ctx.beginPath(); ctx.moveTo(paddingL, yy); ctx.lineTo(sz.w - paddingR, yy); ctx.stroke();
    ctx.fillText(fmtId(val), paddingL - 6, yy);
  }

  // Bars (income blue + expense orange side-by-side).
  var n = data.length;
  var group = plotW / n;
  var barW = Math.max(3, group * 0.36);

  income.forEach(function (v, i) {
    var gx = paddingL + i * group + group / 2;
    var h = (v / yMax) * plotH;
    ctx.fillStyle = '#2563eb';
    ctx.fillRect(gx - barW, paddingT + plotH - h, barW, h);
  });
  expense.forEach(function (v, i) {
    var gx = paddingL + i * group + group / 2;
    var h = (v / yMax) * plotH;
    ctx.fillStyle = '#ea580c';
    ctx.fillRect(gx + 2, paddingT + plotH - h, barW, h);
  });

  // X-axis labels (every other to avoid overlap on small widths).
  ctx.fillStyle = '#6b7280';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'top';
  var skip = n > 8 ? 2 : 1;
  data.forEach(function (r, i) {
    if (i % skip !== 0 && i !== n - 1) return;
    var gx = paddingL + i * group + group / 2;
    ctx.fillText(r.label, gx, paddingT + plotH + 6);
  });

  // Tooltip on hover.
  var tip = document.createElement('div');
  tip.style.cssText =
    'position:absolute;pointer-events:none;background:#1f2330;color:#fff;' +
    'padding:6px 8px;border-radius:6px;font-size:12px;line-height:1.35;' +
    'box-shadow:0 4px 18px rgba(0,0,0,.18);opacity:0;transition:opacity .15s;z-index:20';
  var wrap = canvas.parentNode;
  if (getComputedStyle(wrap).position === 'static') wrap.style.position = 'relative';
  wrap.appendChild(tip);

  canvas.addEventListener('mousemove', function (ev) {
    var rect = canvas.getBoundingClientRect();
    var x = ev.clientX - rect.left;
    var idx = Math.floor((x - paddingL) / group);
    if (idx < 0 || idx >= n) { tip.style.opacity = 0; return; }
    var r = data[idx];
    tip.innerHTML =
      '<b>' + r.label + '</b><br>' +
      '<span style="color:#60a5fa">+ ' + fmtId(income[idx]) + '</span><br>' +
      '<span style="color:#fdba74">- ' + fmtId(expense[idx]) + '</span>';
    tip.style.left = (ev.clientX - rect.left + 12) + 'px';
    tip.style.top  = (ev.clientY - rect.top  + 12) + 'px';
    tip.style.opacity = 1;
  });
  canvas.addEventListener('mouseleave', function () { tip.style.opacity = 0; });
})();
