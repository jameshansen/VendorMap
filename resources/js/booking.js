import Konva from 'konva';

// ---------------------------------------------------------------------------
// Vendor booking view. Read-only floor plan; click a table to select it, then
// book it from the details panel. Coordinates are in centimetres.
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

  drawGrid();
  render();
  fitView();
  wireZoom();
  wireButtons();

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
  document.getElementById('bd-size').textContent = `${Math.round(t.width)}×${Math.round(t.height)} cm` + (t.shape === 'round' ? ' (round)' : '');
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
  document.getElementById('book-btn').addEventListener('click', book);
  document.getElementById('release-btn').addEventListener('click', release);
}

// ---- actions --------------------------------------------------------------
async function book() {
  const t = tableById(selectedId);
  if (!t || !canBook(t)) return;
  const verb = boot.state.autoApprove ? 'Book' : 'Request';
  const ok = confirm(`${verb} table ${t.label || ''} for ${money(t.price)}?\n\n`
    + 'No payment is taken now — the organiser will send payment instructions to follow.');
  if (!ok) return;
  const json = await send(boot.bookUrl, 'POST', { table_id: t.id });
  if (json && !json.error) {
    // Booking made — go to the home page's "Your bookings" section.
    window.location = boot.homeUrl;
  }
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
