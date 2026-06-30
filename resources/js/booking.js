import Konva from 'konva';
import { initUnits, toggleUnit, currentUnit, formatSize } from './units.js';

// ---------------------------------------------------------------------------
// Vendor booking view. Read-only floor plan; click a table to select it, then
// book it through a short wizard. Coordinates are in centimetres.
// ---------------------------------------------------------------------------
const boot = window.__BOOKING__;
const money = (n) => '$' + Number(n || 0).toFixed(2);

const C = {
  gridMinor: 'rgba(40,70,120,0.06)',
  gridMajor: 'rgba(40,70,120,0.13)',
  boundaryStroke: '#2f6df0',
  boundaryFill: 'rgba(47,109,240,0.06)',
  doorFill: '#efa53d',
  doorStroke: '#7a4d12',
  powerFill: '#e9eef6',
  powerStroke: '#2f6df0',
  labelFill: '#1b2430',
  priceFill: 'rgba(27,36,48,0.65)',
  selected: '#1b2430',
};
const STATUS = {
  available: { fill: 'rgba(31,157,104,0.16)', stroke: '#1f9d68' },
  held: { fill: 'rgba(201,145,42,0.20)', stroke: '#c9912a' },
  booked: { fill: 'rgba(120,130,145,0.18)', stroke: '#8a96a5' },
  mine: { fill: 'rgba(47,109,240,0.20)', stroke: '#2f6df0' },
};

let stage, gridLayer, layer;
let boundaryPts = [];
let selectedId = null;

document.addEventListener('DOMContentLoaded', init);

function init() {
  const container = document.getElementById('stage');
  stage = new Konva.Stage({ container: 'stage', width: container.clientWidth, height: container.clientHeight, draggable: true });
  gridLayer = new Konva.Layer({ listening: false });
  layer = new Konva.Layer();
  stage.add(gridLayer, layer);

  initUnits(boot.state.units);
  drawGrid();
  render();
  fitView();
  wireZoom();
  wireButtons();
  wireUnits();

  // Clicking empty space clears the selection.
  stage.on('click tap', (e) => { if (e.target === stage) selectTable(null); });

  window.addEventListener('resize', () => {
    stage.width(container.clientWidth);
    stage.height(container.clientHeight);
  });
  updatePanel();
}

// ---- rendering ------------------------------------------------------------
function render() {
  layer.destroyChildren();
  const data = boot.state.data;

  boundaryPts = polygonToPoints(data.venue.area);
  if (boundaryPts.length) {
    layer.add(new Konva.Line({ points: flatten(boundaryPts), closed: true, listening: false, stroke: C.boundaryStroke, strokeWidth: 6, fill: C.boundaryFill }));
  }

  (data.venue.power_outlets || []).forEach(addPower);
  (data.venue.doors || []).forEach(addDoor);
  (data.tables || []).forEach(addTable);

  layer.draw();
}

function addPower(p) {
  const g = new Konva.Group({ x: p.x, y: p.y, listening: false });
  g.add(new Konva.Circle({ radius: 24, fill: C.powerFill, stroke: C.powerStroke, strokeWidth: 4 }));
  const glyph = new Konva.Text({ text: '⚡', fontSize: 28, fill: C.powerStroke });
  glyph.offsetX(glyph.width() / 2); glyph.offsetY(glyph.height() / 2);
  g.add(glyph);
  layer.add(g);
}

function addDoor(d) {
  layer.add(new Konva.Rect({ x: d.x, y: d.y, width: d.width, height: 18, offsetX: d.width / 2, offsetY: 9, rotation: d.rotation || 0, cornerRadius: 4, fill: C.doorFill, stroke: C.doorStroke, strokeWidth: 2, listening: false }));
}

function addTable(t) {
  const mine = boot.vendorId && t.vendor_id === boot.vendorId;
  const key = mine ? 'mine' : (t.status in STATUS ? t.status : 'available');
  const s = STATUS[key];
  const isSelected = t.id === selectedId;

  const g = new Konva.Group({ x: t.x, y: t.y, rotation: t.rotation || 0 });

  const stroke = isSelected ? C.selected : s.stroke;
  const strokeWidth = isSelected ? 7 : 4;
  const shape = t.shape === 'round'
    ? new Konva.Ellipse({ radiusX: t.width / 2, radiusY: t.height / 2, fill: s.fill, stroke, strokeWidth })
    : new Konva.Rect({ x: -t.width / 2, y: -t.height / 2, width: t.width, height: t.height, cornerRadius: 6, fill: s.fill, stroke, strokeWidth });
  g.add(shape);

  const label = new Konva.Text({ text: t.label || '', fontFamily: 'JetBrains Mono, monospace', fontSize: 26, fill: C.labelFill });
  center(label, 0, -16); g.add(label);
  const price = new Konva.Text({ text: money(t.price), fontFamily: 'JetBrains Mono, monospace', fontSize: 18, fill: C.priceFill });
  center(price, 0, 12); g.add(price);

  if (t.has_power) {
    const badge = new Konva.Text({ text: '⚡', fontSize: 22, fill: C.doorFill });
    badge.position({ x: t.width / 2 - badge.width() - 6, y: -t.height / 2 + 4 });
    g.add(badge);
  }
  if (mine) {
    const tag = new Konva.Text({ text: '✓ yours', fontFamily: 'JetBrains Mono, monospace', fontSize: 14, fill: STATUS.mine.stroke });
    center(tag, 0, 30); g.add(tag);
  }

  g.on('mouseenter', () => { stage.container().style.cursor = 'pointer'; });
  g.on('mouseleave', () => { stage.container().style.cursor = 'grab'; });
  g.on('click tap', (e) => { e.cancelBubble = true; selectTable(t.id); });

  layer.add(g);
}

function center(text, cx, cy) { text.offsetX(text.width() / 2); text.offsetY(text.height() / 2); text.position({ x: cx, y: cy }); }

// ---- selection + panel ----------------------------------------------------
function tableById(id) { return (boot.state.data.tables || []).find((t) => t.id === id) || null; }

function selectTable(id) {
  selectedId = id;
  render();
  updatePanel();
}

function canBook(t) {
  return boot.vendorId
    && boot.state.registrationOpen
    && t.status === 'available'
    && boot.state.myCount < boot.state.perVendor;
}

function updatePanel() {
  const hint = document.getElementById('book-hint');
  const detail = document.getElementById('book-detail');
  const bookBtn = document.getElementById('book-btn');
  const releaseBtn = document.getElementById('release-btn');
  const msg = document.getElementById('book-msg');
  const t = tableById(selectedId);

  if (!t) { hint.hidden = false; detail.hidden = true; return; }
  hint.hidden = true; detail.hidden = false;

  document.getElementById('bd-title').textContent = 'Table ' + (t.label || '');
  document.getElementById('bd-price').textContent = money(t.price);
  document.getElementById('bd-size').textContent = formatSize(t.width, t.height, t.shape === 'round');
  document.getElementById('bd-power').textContent = t.has_power ? 'Yes ⚡' : 'No';

  const mine = boot.vendorId && t.vendor_id === boot.vendorId;
  document.getElementById('bd-status').textContent = mine ? 'Yours' : (t.status === 'available' ? 'Available' : t.status);

  bookBtn.hidden = true; releaseBtn.hidden = true; msg.textContent = '';

  if (mine) {
    releaseBtn.hidden = false;
  } else if (t.status !== 'available') {
    msg.textContent = 'This table is already taken.';
  } else if (!boot.vendorId) {
    msg.textContent = 'Sign in with an approved vendor account to book.';
  } else if (!boot.state.registrationOpen) {
    msg.textContent = 'Registration is closed for this event.';
  } else if (boot.state.myCount >= boot.state.perVendor) {
    msg.textContent = `You've reached your booking limit (${boot.state.perVendor}).`;
  } else {
    bookBtn.hidden = false;
  }
}

function wireButtons() {
  document.getElementById('book-btn').addEventListener('click', startBooking);
  document.getElementById('release-btn').addEventListener('click', release);
}

function wireUnits() {
  const btn = document.getElementById('unit-toggle');
  if (!btn) return;
  const sync = () => { btn.textContent = currentUnit() === 'imperial' ? 'Show in cm' : 'Show in ft/in'; };
  sync();
  btn.addEventListener('click', () => { toggleUnit(); sync(); updatePanel(); });
}

// ---- booking wizard -------------------------------------------------------
let wizard = null; // { table, step, agreed, overlay, profile }

function startBooking() {
  const t = tableById(selectedId);
  if (!t || !canBook(t)) return;
  wizard = {
    table: t,
    step: 1,
    agreed: false,
    profile: Object.assign({ socials: {}, categories: [] }, boot.state.vendor || {}),
  };
  wizard.profile.categories = (wizard.profile.categories || []).slice();
  renderWizard();
}

function closeWizard() {
  if (wizard && wizard.overlay) wizard.overlay.remove();
  wizard = null;
}

function renderWizard() {
  const t = wizard.table;
  if (!wizard.overlay) {
    wizard.overlay = document.createElement('div');
    wizard.overlay.className = 'modal';
    wizard.overlay.addEventListener('click', (e) => { if (e.target === wizard.overlay) closeWizard(); });
    document.body.appendChild(wizard.overlay);
  }
  const steps = ['Table', 'Your details', 'Conditions', 'Confirm'];
  const dots = steps.map((s, i) =>
    `<span class="wiz-dot${i + 1 === wizard.step ? ' active' : ''}${i + 1 < wizard.step ? ' done' : ''}">${i + 1}. ${s}</span>`
  ).join('');

  wizard.overlay.innerHTML =
    `<div class="modal-card wiz-card">
       <div class="wiz-steps">${dots}</div>
       <div class="wiz-body">${stepBody(wizard.step, t)}</div>
       <div class="wiz-foot">
         ${wizard.step > 1 ? '<button class="btn-secondary" data-wiz="back">Back</button>' : '<button class="btn-link" data-wiz="cancel">Cancel</button>'}
         <button class="btn-primary" data-wiz="next" ${nextDisabled() ? 'disabled' : ''}>${nextLabel()}</button>
       </div>
     </div>`;

  wizard.overlay.querySelectorAll('[data-wiz]').forEach((b) => b.addEventListener('click', onWizClick));
  if (wizard.step === 2) wireProfileStep();
  if (wizard.step === 3) {
    const cb = wizard.overlay.querySelector('#wiz-agree');
    cb.addEventListener('change', () => { wizard.agreed = cb.checked; updateNext(); });
  }
}

function stepBody(step, t) {
  if (step === 1) {
    return `<h3>Table ${esc(t.label || '')}</h3>
      <dl class="bd-grid">
        <div><dt>Price</dt><dd>${money(t.price)}</dd></div>
        <div><dt>Size</dt><dd>${formatSize(t.width, t.height, t.shape === 'round')}</dd></div>
        <div><dt>Power</dt><dd>${t.has_power ? 'Yes ⚡' : 'No'}</dd></div>
        <div><dt>Status</dt><dd>Available</dd></div>
      </dl>
      <p class="muted small">No payment is taken here — the organiser will send payment
        instructions to follow after you book.</p>`;
  }
  if (step === 2) {
    const p = wizard.profile;
    const chips = (p.categories || []).map((c) =>
      `<span class="tag-chip"><span>${esc(c)}</span><button type="button" class="tag-chip-x" data-cat-remove="${esc(c)}">&times;</button></span>`
    ).join('');
    const opts = (boot.state.categorySuggestions || []).map((c) => `<option value="${esc(c)}"></option>`).join('');
    return `<h3>Confirm your details</h3>
      <p class="muted small">Please make sure this is accurate and up to date.</p>
      <label>Business name<input type="text" id="wp_business" value="${esc(p.business_name || '')}"></label>
      <label>Contact name<input type="text" id="wp_contact" value="${esc(p.contact_name || '')}"></label>
      <div class="row">
        <label>Phone<input type="text" id="wp_phone" value="${esc(p.phone || '')}"></label>
        <label>Website<input type="text" id="wp_website" value="${esc(p.website || '')}"></label>
      </div>
      <label>What you sell
        <div class="tag-chips" id="wp_chips">${chips}</div>
        <div class="tag-input-row">
          <input type="text" id="wp_cat" list="wiz-cats" placeholder="Add a category…" autocomplete="off">
          <button type="button" class="btn-secondary sm" id="wp_cat_add">Add</button>
        </div>
        <datalist id="wiz-cats">${opts}</datalist>
      </label>
      <p class="muted small" id="wp_msg"></p>`;
  }
  if (step === 3) {
    const html = boot.state.conditionsHtml || '<p class="muted">No conditions have been set.</p>';
    return `<h3>Conditions, liability &amp; rules</h3>
      <div class="wiz-conditions">${html}</div>
      <label class="check"><input type="checkbox" id="wiz-agree" ${wizard.agreed ? 'checked' : ''}>
        I have read and agree to the conditions, liability and rules.</label>`;
  }
  // step 4
  const p = wizard.profile;
  const verb = boot.state.autoApprove ? 'book' : 'request';
  return `<h3>Confirm booking</h3>
    <dl class="bd-grid">
      <div><dt>Table</dt><dd>${esc(t.label || '')}</dd></div>
      <div><dt>Price</dt><dd>${money(t.price)}</dd></div>
      <div><dt>Business</dt><dd>${esc(p.business_name || '')}</dd></div>
      <div><dt>Sells</dt><dd>${(p.categories || []).map(esc).join(', ') || '—'}</dd></div>
    </dl>
    <p class="muted small">You're about to ${verb} this table. No payment is taken now —
      the organiser will send payment instructions to follow.</p>`;
}

function nextLabel() { return wizard.step === 4 ? (boot.state.autoApprove ? 'Confirm booking' : 'Send request') : 'Next'; }
function nextDisabled() { return wizard.step === 3 && !wizard.agreed; }
function updateNext() {
  const btn = wizard.overlay.querySelector('[data-wiz="next"]');
  if (btn) btn.disabled = nextDisabled();
}

function wireProfileStep() {
  const add = () => {
    const input = wizard.overlay.querySelector('#wp_cat');
    const name = (input.value || '').trim();
    if (name && !wizard.profile.categories.some((c) => c.toLowerCase() === name.toLowerCase())) {
      wizard.profile.categories.push(name);
      renderWizard();
    }
  };
  wizard.overlay.querySelector('#wp_cat_add').addEventListener('click', add);
  wizard.overlay.querySelector('#wp_cat').addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); add(); }
  });
  wizard.overlay.querySelectorAll('[data-cat-remove]').forEach((b) => b.addEventListener('click', () => {
    const name = b.getAttribute('data-cat-remove');
    wizard.profile.categories = wizard.profile.categories.filter((c) => c !== name);
    renderWizard();
  }));
}

// Pull the editable profile fields out of the step-2 form into wizard.profile.
function collectProfile() {
  const g = (id) => { const el = wizard.overlay.querySelector(id); return el ? el.value.trim() : ''; };
  wizard.profile.business_name = g('#wp_business');
  wizard.profile.contact_name = g('#wp_contact');
  wizard.profile.phone = g('#wp_phone');
  wizard.profile.website = g('#wp_website');
  // Don't lose a typed-but-not-added category.
  const pendingCat = g('#wp_cat');
  if (pendingCat && !wizard.profile.categories.some((c) => c.toLowerCase() === pendingCat.toLowerCase())) {
    wizard.profile.categories.push(pendingCat);
  }
}

async function onWizClick(e) {
  const action = e.currentTarget.getAttribute('data-wiz');
  if (action === 'cancel') return closeWizard();
  if (action === 'back') { if (wizard.step === 2) collectProfile(); wizard.step--; return renderWizard(); }

  // action === 'next'
  if (wizard.step === 2) {
    collectProfile();
    const ok = await saveProfile();
    if (!ok) return;
  }
  if (wizard.step < 4) { wizard.step++; return renderWizard(); }

  // Final step: place the booking.
  const json = await send(boot.bookUrl, 'POST', { table_id: wizard.table.id, terms_accepted: true });
  if (json && !json.error) { closeWizard(); window.location = boot.homeUrl; }
}

async function saveProfile() {
  const p = wizard.profile;
  if (!p.business_name || !p.contact_name) {
    const msg = wizard.overlay.querySelector('#wp_msg');
    if (msg) msg.textContent = 'Business name and contact name are required.';
    return false;
  }
  const body = {
    business_name: p.business_name, contact_name: p.contact_name,
    phone: p.phone, website: p.website, address: p.address,
    socials: p.socials || {}, categories: p.categories || [],
  };
  const json = await send(boot.profileUrl, 'PUT', body);
  if (json && json.vendor) {
    boot.state.vendor = Object.assign({}, boot.state.vendor, json.vendor);
    return true;
  }
  return !(json && json.error);
}

function esc(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

async function release() {
  const t = tableById(selectedId);
  if (!t) return;
  if (!confirm(`Release your booking for table ${t.label || ''}?`)) return;
  const json = await send(`${boot.releaseBase}/${t.id}`, 'DELETE');
  if (json && json.state) {
    boot.state = json.state;
    selectTable(null);
    setStatus(json.message || 'Booking released.');
  }
}

async function send(url, method, body) {
  setStatus('Working…');
  try {
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': boot.csrf },
      body: body ? JSON.stringify(body) : undefined,
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) { setStatus(json.error || 'Something went wrong.'); return json; }
    setStatus(json.message || 'Done.');
    return json;
  } catch (e) {
    setStatus('Network error. Please try again.');
    console.error(e);
    return null;
  }
}

function setStatus(text) { const s = document.getElementById('status'); if (s) s.textContent = text; }

// ---- canvas helpers -------------------------------------------------------
function drawGrid() {
  const min = -2000, max = 4000, step = 100;
  for (let v = min; v <= max; v += step) {
    const major = v % 500 === 0;
    const color = major ? C.gridMajor : C.gridMinor;
    const w = major ? 2 : 1;
    gridLayer.add(new Konva.Line({ points: [v, min, v, max], stroke: color, strokeWidth: w }));
    gridLayer.add(new Konva.Line({ points: [min, v, max, v], stroke: color, strokeWidth: w }));
  }
}

function wireZoom() {
  stage.on('wheel', (e) => {
    e.evt.preventDefault();
    const old = stage.scaleX();
    const pointer = stage.getPointerPosition();
    const to = { x: (pointer.x - stage.x()) / old, y: (pointer.y - stage.y()) / old };
    const next = e.evt.deltaY > 0 ? old / 1.1 : old * 1.1;
    stage.scale({ x: next, y: next });
    stage.position({ x: pointer.x - to.x * next, y: pointer.y - to.y * next });
  });
}

// Fit to the boundary if present, otherwise to whatever elements exist.
function fitView() {
  const data = boot.state.data;
  let pts = boundaryPts.slice();
  if (pts.length < 2) {
    (data.tables || []).forEach((t) => pts.push({ x: t.x, y: t.y }));
    (data.venue.doors || []).forEach((d) => pts.push({ x: d.x, y: d.y }));
    (data.venue.power_outlets || []).forEach((p) => pts.push({ x: p.x, y: p.y }));
  }
  if (pts.length < 2) { stage.scale({ x: 0.3, y: 0.3 }); stage.position({ x: 60, y: 60 }); return; }

  const xs = pts.map((p) => p.x), ys = pts.map((p) => p.y);
  const minX = Math.min(...xs), maxX = Math.max(...xs), minY = Math.min(...ys), maxY = Math.max(...ys);
  const pad = 160;
  const scale = Math.min(stage.width() / (maxX - minX + pad * 2), stage.height() / (maxY - minY + pad * 2));
  stage.scale({ x: scale, y: scale });
  stage.position({ x: -(minX - pad) * scale, y: -(minY - pad) * scale });
}

function polygonToPoints(area) {
  if (!area || !area.coordinates || !area.coordinates.length) return [];
  const ring = area.coordinates[0].slice();
  if (ring.length > 1) {
    const a = ring[0], b = ring[ring.length - 1];
    if (a[0] === b[0] && a[1] === b[1]) ring.pop();
  }
  return ring.map(([x, y]) => ({ x, y }));
}
const flatten = (pts) => pts.flatMap((p) => [p.x, p.y]);
