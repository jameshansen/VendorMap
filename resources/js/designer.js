import Konva from 'konva';
import { initUnits, toggleUnit, currentUnit, cmToSmall, smallToCm, smallUnit, cmToBig, bigToCm, bigUnit } from './units.js';

// ---------------------------------------------------------------------------
// Setup. All coordinates are in centimetres; the stage scale handles zoom.
// ---------------------------------------------------------------------------
const boot = window.__DESIGNER__;
const money = (n) => '$' + Number(n || 0).toFixed(2);

// Canvas colours (light theme). Kept in one place so the look is easy to change.
const C = {
  gridMinor: 'rgba(40,70,120,0.06)',
  gridMajor: 'rgba(40,70,120,0.13)',
  boundaryStroke: '#2f6df0',
  boundaryFill: 'rgba(47,109,240,0.06)',
  handleFill: '#ffffff',
  handleStroke: '#2f6df0',
  doorFill: '#efa53d',
  doorStroke: '#7a4d12',
  powerFill: '#e9eef6',
  powerStroke: '#2f6df0',
  powerGlyph: '#2f6df0',
  labelFill: '#1b2430',
  priceFill: 'rgba(27,36,48,0.65)',
};
const STATUS_STYLE = {
  available: { fill: 'rgba(31,157,104,0.16)', stroke: '#1f9d68' },
  held: { fill: 'rgba(201,145,42,0.18)', stroke: '#c9912a' },
  booked: { fill: 'rgba(207,75,75,0.16)', stroke: '#cf4b4b' },
};

// Built-in presets. User-saved ones are merged in from localStorage.
const DEFAULT_PRESETS = {
  table: [
    { name: '6ft trestle', width: 180, height: 75, shape: 'rect' },
    { name: '4ft trestle', width: 120, height: 75, shape: 'rect' },
    { name: 'Round 150', width: 150, height: 150, shape: 'round' },
    { name: 'Square 90', width: 90, height: 90, shape: 'rect' },
  ],
  door: [
    { name: 'Single 90', width: 90, type: 'entrance' },
    { name: 'Double 180', width: 180, type: 'entrance' },
    { name: 'Emergency', width: 120, type: 'emergency' },
    { name: 'Loading bay', width: 300, type: 'loading' },
  ],
  power: [
    { name: '15A double', amperage: 15, voltage: 120, outlets: 2 },
    { name: '20A single', amperage: 20, voltage: 120, outlets: 1 },
    { name: '30A drop', amperage: 30, voltage: 240, outlets: 1 },
  ],
};

let stage, gridLayer, layer, tr;
let tool = 'details';
let selected = null;

let boundaryPts = [];
let doors = [];
let power = [];
let tables = [];
let boundaryLine, handleGroup;

let backendPresets = groupPresets(boot.presets || []);
const activePreset = { table: null, door: null, power: null };
let dragPreset = null; // preset being dragged from the palette
let ctxNode = null;    // node the context menu is acting on
let dirty = false;     // unsaved changes guard for venue switching
let selectedVenueId = null; // venue currently shown/edited (committed only on save)
const DEBUG = true;
const log = (...a) => { if (DEBUG) console.log('[VendorMap]', ...a); };

document.addEventListener('DOMContentLoaded', init);

function init() {
  const container = document.getElementById('stage');
  stage = new Konva.Stage({ container: 'stage', width: container.clientWidth, height: container.clientHeight, draggable: true });
  gridLayer = new Konva.Layer({ listening: false });
  layer = new Konva.Layer();
  stage.add(gridLayer, layer);

  tr = new Konva.Transformer({ rotationSnaps: [0, 90, 180, 270], padding: 4, anchorStroke: C.boundaryStroke, borderStroke: C.boundaryStroke });
  layer.add(tr);

  drawGrid();
  loadData(boot.data);
  fitView();
  wireToolbar();
  wirePanel();
  wireStage();
  wirePaletteDrop();
  wireContextMenu();
  wireVenue();
  wireEventFields();
  wireBoundaryPanel();
  wireUnits();
  initEventForm();
  selectedVenueId = boot.data.event.venue_id;
  log('init: committed venue on event =', selectedVenueId);

  window.addEventListener('resize', () => { stage.width(container.clientWidth); stage.height(container.clientHeight); });
  setStatus('Ready');
}

// ---------------------------------------------------------------------------
// Loading + rendering
// ---------------------------------------------------------------------------
function loadData(data) {
  layer.find('.table, .door, .power, .boundary, .handle').forEach((n) => n.destroy());
  tr.nodes([]); selected = null;

  boundaryPts = polygonToPoints(data.venue.area);
  doors = (data.venue.doors || []).map((d) => ({ ...d }));
  power = (data.venue.power_outlets || []).map((p) => ({ ...p }));
  tables = (data.tables || []).map((t) => ({ ...t, price: Number(t.price) }));

  boundaryLine = new Konva.Line({ points: flatten(boundaryPts), closed: true, name: 'boundary', listening: false, stroke: C.boundaryStroke, strokeWidth: 6, fill: C.boundaryFill });
  layer.add(boundaryLine); boundaryLine.moveToBottom();

  if (handleGroup) handleGroup.destroy();
  handleGroup = new Konva.Group();
  layer.add(handleGroup);
  rebuildHandles();

  power.forEach(addPowerNode);
  doors.forEach(addDoorNode);
  tables.forEach(addTableNode);

  setTool(tool);
  layer.draw();
  dirty = false;
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

// ---- node builders --------------------------------------------------------
function addPowerNode(p) {
  const g = new Konva.Group({ x: p.x, y: p.y, name: 'power', draggable: true });
  g._kind = 'power'; g._model = p;
  g.add(new Konva.Circle({ radius: 24, fill: C.powerFill, stroke: C.powerStroke, strokeWidth: 4 }));
  const glyph = new Konva.Text({ text: '\u26A1', fontSize: 28, fill: C.powerGlyph });
  glyph.offsetX(glyph.width() / 2); glyph.offsetY(glyph.height() / 2);
  g.add(glyph);
  wireNode(g); layer.add(g); return g;
}

function addDoorNode(d) {
  const r = new Konva.Rect({ x: d.x, y: d.y, width: d.width, height: 18, offsetX: d.width / 2, offsetY: 9, rotation: d.rotation || 0, cornerRadius: 4, fill: C.doorFill, stroke: C.doorStroke, strokeWidth: 2, name: 'door', draggable: true });
  r._kind = 'door'; r._model = d;
  r.on('dragmove', () => snapDoorToWall(r));
  r.on('transformend', () => {
    const sx = r.scaleX();
    r.scaleX(1); r.scaleY(1);
    d.width = Math.max(20, Math.round(r.width() * sx));
    r.width(d.width); r.offsetX(d.width / 2);
    snapDoorToWall(r);
    if (selected === r) populatePanel();
    markDirty();
  });
  wireNode(r); layer.add(r); return r;
}

function addTableNode(t) {
  const g = new Konva.Group({ x: t.x, y: t.y, rotation: t.rotation || 0, name: 'table', draggable: true });
  g._kind = 'table'; g._model = t;
  const shape = makeTableShape(t);
  const label = new Konva.Text({ text: t.label || '', fontFamily: 'JetBrains Mono, monospace', fontSize: 28, fill: C.labelFill });
  const price = new Konva.Text({ text: money(t.price), fontFamily: 'JetBrains Mono, monospace', fontSize: 20, fill: C.priceFill });
  const powerBadge = new Konva.Text({ text: '⚡', fontSize: 26, fill: C.doorFill, listening: false, visible: !!t.has_power });
  g.add(shape, label, price, powerBadge);
  g._shape = shape; g._label = label; g._price = price; g._powerBadge = powerBadge;
  recenter(label, 0, -16); recenter(price, 0, 14);
  positionPowerBadge(g);
  g.on('transformend', () => {
    const sx = g.scaleX(), sy = g.scaleY();
    g.scale({ x: 1, y: 1 });
    t.width = Math.max(30, Math.round(t.width * sx));
    t.height = Math.max(30, Math.round(t.height * sy));
    t.rotation = Math.round(g.rotation());
    resizeTableShape(g);
    recenter(label, 0, -16); recenter(price, 0, 14);
    if (selected === g) populatePanel();
    markDirty();
  });
  wireNode(g); layer.add(g); return g;
}

function makeTableShape(t) {
  const s = STATUS_STYLE[t.status] || STATUS_STYLE.available;
  if (t.shape === 'round') return new Konva.Ellipse({ radiusX: t.width / 2, radiusY: t.height / 2, ...s, strokeWidth: 4 });
  return new Konva.Rect({ x: -t.width / 2, y: -t.height / 2, width: t.width, height: t.height, cornerRadius: 6, ...s, strokeWidth: 4 });
}
function resizeTableShape(g) {
  const t = g._model;
  if (t.shape === 'round') { g._shape.radiusX(t.width / 2); g._shape.radiusY(t.height / 2); }
  else { g._shape.width(t.width); g._shape.height(t.height); g._shape.x(-t.width / 2); g._shape.y(-t.height / 2); }
  positionPowerBadge(g);
}
// Tuck the ⚡ badge into the table's top-right corner.
function positionPowerBadge(g) {
  if (!g._powerBadge) return;
  const t = g._model;
  g._powerBadge.position({ x: t.width / 2 - g._powerBadge.width() - 6, y: -t.height / 2 + 4 });
}
function rebuildTableShape(g) {
  g._shape.destroy();
  g._shape = makeTableShape(g._model);
  g.add(g._shape); g._shape.moveToBottom();
}
function recenter(text, cx, cy) { text.offsetX(text.width() / 2); text.offsetY(text.height() / 2); text.position({ x: cx, y: cy }); }

function rebuildHandles() {
  handleGroup.destroyChildren();
  boundaryPts.forEach((pt, i) => {
    const h = new Konva.Circle({ x: pt.x, y: pt.y, radius: 14, name: 'handle', fill: C.handleFill, stroke: C.handleStroke, strokeWidth: 4, draggable: tool === 'select' || tool === 'boundary' });
    h._kind = 'vertex'; h._index = i;
    h.on('dragmove', () => { boundaryPts[i] = { x: Math.round(h.x()), y: Math.round(h.y()) }; boundaryLine.points(flatten(boundaryPts)); markDirty(); });
    h.on('dragend', resnapDoors);
    wireNode(h); handleGroup.add(h);
  });
}

// ---------------------------------------------------------------------------
// Door snapping to walls
// ---------------------------------------------------------------------------
function nearestOnSegment(px, py, ax, ay, bx, by) {
  const dx = bx - ax, dy = by - ay;
  const len2 = dx * dx + dy * dy || 1;
  let t = ((px - ax) * dx + (py - ay) * dy) / len2;
  t = Math.max(0, Math.min(1, t));
  return { x: ax + t * dx, y: ay + t * dy, angle: Math.atan2(dy, dx) * 180 / Math.PI };
}
function snapDoorToWall(node) {
  if (boundaryPts.length < 2) return;
  let best = null, bestD = Infinity;
  const n = boundaryPts.length;
  for (let i = 0; i < n; i++) {
    const a = boundaryPts[i], b = boundaryPts[(i + 1) % n];
    const c = nearestOnSegment(node.x(), node.y(), a.x, a.y, b.x, b.y);
    const d = Math.hypot(c.x - node.x(), c.y - node.y());
    if (d < bestD) { bestD = d; best = c; }
  }
  if (best) {
    node.x(Math.round(best.x)); node.y(Math.round(best.y)); node.rotation(Math.round(best.angle));
    const m = node._model; m.x = node.x(); m.y = node.y(); m.rotation = node.rotation();
  }
}
function resnapDoors() { layer.find('.door').forEach(snapDoorToWall); }

// ---------------------------------------------------------------------------
// Selection + interaction
// ---------------------------------------------------------------------------
function wireNode(node) {
  node.on('dragstart', () => { if (tool === 'select') select(node); });
  node.on('dragend', () => {
    const m = node._model;
    if (m) { m.x = Math.round(node.x()); m.y = Math.round(node.y()); }
    if (node._kind === 'vertex') boundaryPts[node._index] = { x: Math.round(node.x()), y: Math.round(node.y()) };
    if (selected === node) populatePanel();
    markDirty();
  });
}
function ownerOf(target) { let n = target; while (n && !n._kind) n = n.getParent(); return n; }

function wireStage() {
  let down = null;
  stage.on('pointerdown', () => { down = stage.getPointerPosition(); });
  stage.on('pointerup', (e) => {
    if (e.evt && e.evt.button === 2) return; // right-click is for the context menu
    const up = stage.getPointerPosition();
    const moved = down && up ? Math.hypot(up.x - down.x, up.y - down.y) : 0;
    if (moved > 6) return;
    if (tool === 'select' || tool === 'details') {
      const owner = ownerOf(e.target);
      if (owner && owner._kind) select(owner); else deselect();
    } else if (tool === 'table' || tool === 'door' || tool === 'power') {
      // Clicking an existing object means "select that", not "add another".
      const owner = ownerOf(e.target);
      if (owner && owner._kind && owner._kind !== 'vertex') { setTool('select'); select(owner); }
      else if (e.target === stage || e.target === boundaryLine) handlePlace(relativePointer());
    } else if (e.target === stage || e.target === boundaryLine) {
      handlePlace(relativePointer());
    }
  });
  stage.on('wheel', (e) => {
    e.evt.preventDefault();
    hideContextMenu();
    const old = stage.scaleX();
    const pointer = stage.getPointerPosition();
    const to = { x: (pointer.x - stage.x()) / old, y: (pointer.y - stage.y()) / old };
    const next = e.evt.deltaY > 0 ? old / 1.1 : old * 1.1;
    stage.scale({ x: next, y: next });
    stage.position({ x: pointer.x - to.x * next, y: pointer.y - to.y * next });
  });
  stage.on('dragstart', hideContextMenu);
}

function relativePointer() {
  const t = stage.getAbsoluteTransform().copy().invert();
  return t.point(stage.getPointerPosition());
}

function modelFromPreset(kind, p, x, y) {
  if (kind === 'table') return { id: null, label: 'T' + (tables.length + 1), x, y, width: p.width, height: p.height, rotation: 0, shape: p.shape || 'rect', price: 0, status: 'available', has_power: false };
  if (kind === 'door') return { id: null, label: null, type: p.type || 'entrance', x, y, width: p.width, rotation: 0 };
  return { id: null, label: null, x, y, amperage: p.amperage ?? 15, voltage: p.voltage ?? 120, outlets: p.outlets ?? 1 };
}
function addObject(kind, preset, x, y) {
  const m = modelFromPreset(kind, preset, Math.round(x), Math.round(y));
  let node;
  if (kind === 'table') { tables.push(m); node = addTableNode(m); }
  else if (kind === 'door') { doors.push(m); node = addDoorNode(m); snapDoorToWall(node); }
  else { power.push(m); node = addPowerNode(m); }
  layer.draw();
  markDirty();
  return node;
}
function nodeForModel(m) { return layer.getChildren().find((n) => n._model === m) || null; }

function handlePlace(pos) {
  const x = Math.round(pos.x), y = Math.round(pos.y);
  if (tool === 'boundary') {
    boundaryPts.push({ x, y });
    boundaryLine.points(flatten(boundaryPts));
    rebuildHandles(); resnapDoors(); layer.draw();
    markDirty();
    return;
  }
  // Guard against accidental duplicates: if a same-kind object already sits
  // right where the user clicked, select it instead of stacking a new one.
  const arr = tool === 'table' ? tables : tool === 'door' ? doors : power;
  const near = arr.find((m) => Math.hypot(m.x - x, m.y - y) < 30);
  if (near) { const n = nodeForModel(near); if (n) { setTool('select'); select(n); return; } }

  const preset = activePreset[tool] || presetsFor(tool)[0];
  select(addObject(tool, preset, x, y));
}

function select(node) {
  selected = node;
  if (node._kind === 'table') tr.setAttrs({ enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right'], rotateEnabled: true });
  else if (node._kind === 'door') tr.setAttrs({ enabledAnchors: ['middle-left', 'middle-right'], rotateEnabled: false });
  tr.nodes(node._kind === 'table' || node._kind === 'door' ? [node] : []);
  updatePanel();
}
function deselect() { selected = null; tr.nodes([]); updatePanel(); }

// ---------------------------------------------------------------------------
// Toolbar + panel + presets
// ---------------------------------------------------------------------------
function wireToolbar() {
  document.querySelectorAll('.tools button').forEach((btn) => btn.addEventListener('click', () => setTool(btn.dataset.tool)));
  document.getElementById('save').addEventListener('click', save);
}
function setTool(next) {
  tool = next;
  document.querySelectorAll('.tools button').forEach((b) => b.classList.toggle('active', b.dataset.tool === next));
  const interactive = next === 'select' || next === 'details';
  stage.draggable(interactive);
  layer.find('.table, .door, .power').forEach((n) => n.draggable(interactive));
  handleGroup.getChildren().forEach((h) => h.draggable(interactive || next === 'boundary'));
  if (next === 'select') updatePanel(); else deselect();
}

function updatePanel() {
  document.getElementById('hint').hidden = true;
  document.getElementById('palette').hidden = true;
  ['table', 'door', 'power', 'vertex', 'event', 'boundary'].forEach((k) => { document.getElementById('panel-' + k).hidden = true; });
  if (selected) { document.getElementById('panel-' + selected._kind).hidden = false; populatePanel(); return; }
  if (tool === 'details') { document.getElementById('panel-event').hidden = false; return; }
  if (tool === 'boundary') { document.getElementById('panel-boundary').hidden = false; prefillBoundaryPanel(); return; }
  if (tool === 'door' || tool === 'power' || tool === 'table') { document.getElementById('palette').hidden = false; renderPalette(tool); return; }
  document.getElementById('hint').hidden = false;
}

function groupPresets(list) {
  const g = { table: [], door: [], power: [] };
  list.forEach((p) => { (g[p.kind] = g[p.kind] || []).push({ id: p.id, name: p.name, ...(p.data || {}) }); });
  return g;
}
function presetsFor(kind) { return (DEFAULT_PRESETS[kind] || []).concat(backendPresets[kind] || []); }

function renderPalette(kind) {
  document.getElementById('palette-title').textContent = kind.charAt(0).toUpperCase() + kind.slice(1) + ' presets';
  const list = document.getElementById('palette-list');
  list.innerHTML = '';
  const items = presetsFor(kind);
  if (!activePreset[kind]) activePreset[kind] = items[0];
  items.forEach((p) => {
    const chip = document.createElement('div');
    chip.className = 'chip' + (activePreset[kind] === p ? ' active' : '');
    chip.draggable = true;
    const label = document.createElement('span');
    label.textContent = p.name;
    chip.appendChild(label);
    if (p.id) { // saved (backend) presets can be removed
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'chip-del';
      del.textContent = '\u00D7';
      del.title = 'Delete preset for everyone';
      del.addEventListener('click', (e) => { e.stopPropagation(); deletePreset(kind, p); });
      chip.appendChild(del);
    }
    chip.addEventListener('click', () => { activePreset[kind] = p; renderPalette(kind); });
    chip.addEventListener('dragstart', (e) => { dragPreset = { kind, preset: p }; e.dataTransfer.effectAllowed = 'copy'; });
    chip.addEventListener('dragend', () => { dragPreset = null; });
    list.appendChild(chip);
  });
}

function wirePaletteDrop() {
  const container = stage.container();
  container.addEventListener('dragover', (e) => { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; });
  container.addEventListener('drop', (e) => {
    e.preventDefault();
    if (!dragPreset) return;
    const rect = container.getBoundingClientRect();
    const screen = { x: e.clientX - rect.left, y: e.clientY - rect.top };
    const world = stage.getAbsoluteTransform().copy().invert().point(screen);
    select(addObject(dragPreset.kind, dragPreset.preset, world.x, world.y));
    dragPreset = null;
  });
}

const val = (id) => document.getElementById(id).value;
const setVal = (id, v) => { document.getElementById(id).value = v; };

function wirePanel() {
  const on = (id, ev, fn) => document.getElementById(id).addEventListener(ev, (e) => { fn(e); markDirty(); });
  on('t_label', 'input', () => { selected._model.label = val('t_label'); selected._label.text(val('t_label')); recenter(selected._label, 0, -16); });
  on('t_price', 'input', () => { selected._model.price = Number(val('t_price')); selected._price.text(money(selected._model.price)); recenter(selected._price, 0, 14); });
  on('t_width', 'input', () => { selected._model.width = smallToCm(val('t_width')); resizeTableShape(selected); });
  on('t_height', 'input', () => { selected._model.height = smallToCm(val('t_height')); resizeTableShape(selected); });
  on('t_rotation', 'input', () => { selected._model.rotation = Number(val('t_rotation')); selected.rotation(selected._model.rotation); });
  on('t_shape', 'change', () => { selected._model.shape = val('t_shape'); rebuildTableShape(selected); });
  on('t_status', 'change', () => { selected._model.status = val('t_status'); applyStyle(selected._shape, selected._model.status); });
  on('t_power', 'change', () => { selected._model.has_power = document.getElementById('t_power').checked; selected._powerBadge.visible(selected._model.has_power); layer.batchDraw(); });
  on('d_label', 'input', () => { selected._model.label = val('d_label'); });
  on('d_type', 'change', () => { selected._model.type = val('d_type'); });
  on('d_width', 'input', () => { const w = smallToCm(val('d_width')); selected._model.width = w; selected.width(w); selected.offsetX(w / 2); });
  on('d_rotation', 'input', () => { selected._model.rotation = Number(val('d_rotation')); selected.rotation(selected._model.rotation); });
  on('p_label', 'input', () => { selected._model.label = val('p_label'); });
  on('p_amperage', 'input', () => { selected._model.amperage = Number(val('p_amperage')); });
  on('p_voltage', 'input', () => { selected._model.voltage = Number(val('p_voltage')); });
  on('p_outlets', 'input', () => { selected._model.outlets = Number(val('p_outlets')); });
  document.querySelectorAll('[data-delete]').forEach((b) => b.addEventListener('click', deleteSelected));
}
function applyStyle(shape, status) { const s = STATUS_STYLE[status] || STATUS_STYLE.available; shape.fill(s.fill); shape.stroke(s.stroke); }

function populatePanel() {
  if (!selected) return;
  const m = selected._model;
  if (selected._kind === 'table') {
    setVal('t_label', m.label || ''); setVal('t_price', m.price); setVal('t_width', cmToSmall(m.width)); setVal('t_height', cmToSmall(m.height));
    setVal('t_rotation', Math.round(selected.rotation())); setVal('t_shape', m.shape); setVal('t_status', m.status);
    document.getElementById('t_power').checked = !!m.has_power;
  } else if (selected._kind === 'door') {
    setVal('d_label', m.label || ''); setVal('d_type', m.type); setVal('d_width', cmToSmall(m.width)); setVal('d_rotation', Math.round(selected.rotation()));
  } else if (selected._kind === 'power') {
    setVal('p_label', m.label || ''); setVal('p_amperage', m.amperage ?? 0); setVal('p_voltage', m.voltage ?? 0); setVal('p_outlets', m.outlets ?? 1);
  }
}

function deleteSelected() {
  if (!selected) return;
  const kind = selected._kind;
  if (kind === 'vertex') {
    boundaryPts.splice(selected._index, 1);
    boundaryLine.points(flatten(boundaryPts));
    rebuildHandles();
  } else {
    const arr = kind === 'table' ? tables : kind === 'door' ? doors : power;
    const i = arr.indexOf(selected._model);
    if (i >= 0) arr.splice(i, 1);
    selected.destroy();
  }
  deselect();
  layer.draw();
  markDirty();
}

// ---------------------------------------------------------------------------
// Right-click context menu ("save as preset")
// ---------------------------------------------------------------------------
function wireContextMenu() {
  stage.on('contextmenu', (e) => {
    e.evt.preventDefault();
    const owner = ownerOf(e.target);
    if (owner && owner._kind && owner._kind !== 'vertex') showContextMenu(owner, e.evt.clientX, e.evt.clientY);
    else hideContextMenu();
  });
  document.addEventListener('pointerdown', (e) => {
    const m = document.getElementById('ctxmenu');
    if (!m.hidden && !m.contains(e.target)) hideContextMenu();
  });
}
function showContextMenu(node, clientX, clientY) {
  ctxNode = node;
  const menu = document.getElementById('ctxmenu');
  menu.innerHTML = '';
  const item = (label, fn) => { const d = document.createElement('div'); d.className = 'ctx-item'; d.textContent = label; d.addEventListener('click', () => { fn(); hideContextMenu(); }); menu.appendChild(d); };
  item('Save as preset', () => savePresetFrom(node));
  item('Delete', () => { select(node); deleteSelected(); });
  menu.style.left = clientX + 'px';
  menu.style.top = clientY + 'px';
  menu.hidden = false;
}
function hideContextMenu() { const m = document.getElementById('ctxmenu'); if (m) m.hidden = true; ctxNode = null; }

async function savePresetFrom(node) {
  const k = node._kind, m = node._model;
  const fallback = k.charAt(0).toUpperCase() + k.slice(1) + ' ' + ((backendPresets[k] || []).length + 1);
  const name = prompt('Preset name', fallback);
  if (name === null) return;
  let data;
  if (k === 'table') data = { width: m.width, height: m.height, shape: m.shape };
  else if (k === 'door') data = { width: m.width, type: m.type };
  else data = { amperage: m.amperage, voltage: m.voltage, outlets: m.outlets };
  try {
    const res = await fetch(boot.presetsUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': boot.csrf },
      body: JSON.stringify({ kind: k, name, data }),
    });
    if (!res.ok) { setStatus('Preset save failed'); return; }
    const saved = await res.json();
    (backendPresets[k] = backendPresets[k] || []).push({ id: saved.id, name: saved.name, ...(saved.data || {}) });
    if (tool === k && !selected) renderPalette(k);
    setStatus('Preset saved');
  } catch (e) { setStatus('Preset save failed'); console.error(e); }
}

async function deletePreset(kind, preset) {
  if (!preset.id) return; // built-in presets aren't stored, so can't be removed
  if (!confirm('Delete preset "' + preset.name + '" for everyone?')) return;
  try {
    const res = await fetch(boot.presetsUrl + '/' + preset.id, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': boot.csrf },
    });
    if (!res.ok) { setStatus('Delete failed'); return; }
    backendPresets[kind] = (backendPresets[kind] || []).filter((p) => p !== preset);
    if (activePreset[kind] === preset) activePreset[kind] = null;
    renderPalette(kind);
  } catch (e) { setStatus('Delete failed'); console.error(e); }
}

// ---------------------------------------------------------------------------
// Venue selection / creation / duplication
// ---------------------------------------------------------------------------
function wireVenue() {
  const sel = document.getElementById('venue-select');
  sel.addEventListener('change', () => {
    const v = sel.value;
    if (v === '__new__') { sel.value = String(selectedVenueId); createVenue(); return; }
    if (!confirmDiscard()) { sel.value = String(selectedVenueId); return; }
    log('preview venue', v, '(not saved yet)');
    getJson(boot.venuePreviewBase + '/' + v)
      .then(applyServerState)
      .catch((err) => { log('venue switch failed', err); setStatus('Switch failed'); sel.value = String(selectedVenueId); });
  });
  document.getElementById('dup-venue').addEventListener('click', duplicateVenue);
}
function createVenue() {
  if (!confirmDiscard()) return;
  const name = prompt('Name for the new venue');
  if (!name) return;
  const w = Number(prompt('Width in metres', '20'));
  const h = Number(prompt('Depth in metres', '14'));
  if (!w || !h) return;
  postVenue(boot.venueNewUrl, { name, width: Math.round(w * 100), height: Math.round(h * 100) });
}
function duplicateVenue() {
  if (!confirmDiscard()) return;
  const name = prompt('Name for the duplicated venue');
  if (!name) return;
  log('duplicate source venue', selectedVenueId, 'as', name);
  postVenue(boot.venueDuplicateUrl, { name, source_venue_id: selectedVenueId });
}
async function getJson(url) {
  setStatus('Loading\u2026');
  const res = await fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
  if (!res.ok) { setStatus('Failed'); throw new Error('GET ' + url + ' -> ' + res.status); }
  return res.json();
}
async function postVenue(url, body) {
  setStatus('Working\u2026');
  try {
    const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': boot.csrf }, body: JSON.stringify(body) });
    if (!res.ok) { setStatus('Failed'); log('postVenue failed', res.status); return; }
    applyServerState(await res.json());
  } catch (e) { setStatus('Failed'); console.error(e); }
}
// Re-render the designer from a fresh server payload. This is a *preview*: the
// shown venue becomes the pending selection but is only written to the event on save.
function applyServerState(resp) {
  boot.data = resp.data;
  selectedVenueId = resp.data.event.venue_id;
  log('showing venue', selectedVenueId, '(pending; commits on save)');
  const sel = document.getElementById('venue-select');
  sel.innerHTML = '';
  resp.venues.forEach((v) => {
    const o = document.createElement('option');
    o.value = String(v.id);
    o.textContent = v.name;
    if (v.id === selectedVenueId) o.selected = true;
    sel.appendChild(o);
  });
  const nu = document.createElement('option');
  nu.value = '__new__';
  nu.textContent = '+ New venue\u2026';
  sel.appendChild(nu);
  loadData(resp.data);
  initEventForm();
  fitView();
  setStatus('Ready');
}
function confirmDiscard() {
  return !dirty || confirm('You have unsaved changes that will be lost. Continue?');
}

// ---------------------------------------------------------------------------
// Boundary panel: clear / rebuild the room from a width × height
// ---------------------------------------------------------------------------
function boundaryBounds() {
  if (boundaryPts.length < 2) return null;
  const xs = boundaryPts.map((p) => p.x), ys = boundaryPts.map((p) => p.y);
  return { minX: Math.min(...xs), minY: Math.min(...ys), maxX: Math.max(...xs), maxY: Math.max(...ys) };
}
function prefillBoundaryPanel() {
  const b = boundaryBounds();
  const wCm = b ? b.maxX - b.minX : 2000; // default 20m × 14m
  const hCm = b ? b.maxY - b.minY : 1400;
  setVal('b_width', cmToBig(wCm));
  setVal('b_height', cmToBig(hCm));
}
function recreateBoundaryRect(wCm, hCm) {
  if (wCm < 10 || hCm < 10) return;
  const b = boundaryBounds();
  const x0 = b ? b.minX : 0, y0 = b ? b.minY : 0;
  boundaryPts = [
    { x: x0, y: y0 }, { x: x0 + wCm, y: y0 },
    { x: x0 + wCm, y: y0 + hCm }, { x: x0, y: y0 + hCm },
  ];
  boundaryLine.points(flatten(boundaryPts));
  rebuildHandles(); resnapDoors(); layer.draw();
  fitView(); markDirty();
}
function clearBoundary() {
  boundaryPts = [];
  boundaryLine.points([]);
  rebuildHandles(); layer.draw();
  markDirty();
}
function wireBoundaryPanel() {
  document.getElementById('b_recreate').addEventListener('click', () => {
    recreateBoundaryRect(bigToCm(val('b_width')), bigToCm(val('b_height')));
  });
  document.getElementById('b_clear').addEventListener('click', () => {
    if (boundaryPts.length && !confirm('Clear the room boundary?')) return;
    clearBoundary();
  });
}

// ---------------------------------------------------------------------------
// Units (cm/m <-> in/ft) toggle
// ---------------------------------------------------------------------------
function wireUnits() {
  initUnits(boot.units);
  refreshUnitLabels();
  document.getElementById('unit-toggle').addEventListener('click', () => {
    toggleUnit();
    refreshUnitLabels();
    if (selected) populatePanel();
    if (tool === 'boundary') prefillBoundaryPanel();
  });
}
function refreshUnitLabels() {
  document.querySelectorAll('.unit-sm').forEach((s) => { s.textContent = smallUnit(); });
  document.querySelectorAll('.unit-bg').forEach((s) => { s.textContent = bigUnit(); });
  document.getElementById('unit-toggle').textContent = currentUnit() === 'imperial' ? 'ft/in' : 'cm';
}

// ---------------------------------------------------------------------------
// Event details modal
// ---------------------------------------------------------------------------
function wireEventFields() {
  document.querySelectorAll('#panel-event input').forEach((i) => i.addEventListener('input', markDirty));
}
function initEventForm() {
  const e = boot.data.event || {};
  setVal('e_name', e.name || '');
  setVal('e_starts', e.starts_at || '');
  setVal('e_ends', e.ends_at || '');
  document.getElementById('e_public').checked = !!e.is_public;
  setVal('e_reg_open', e.registration_opens_at || '');
  setVal('e_reg_close', e.registration_closes_at || '');
  setVal('e_cancel', e.cancellation_deadline || '');
}
function eventPayload() {
  return {
    name: val('e_name'),
    starts_at: val('e_starts') || null,
    ends_at: val('e_ends') || null,
    is_public: document.getElementById('e_public').checked,
    registration_opens_at: val('e_reg_open') || null,
    registration_closes_at: val('e_reg_close') || null,
    cancellation_deadline: val('e_cancel') || null,
  };
}

function markDirty() { dirty = true; }

// ---------------------------------------------------------------------------
// Save + fit
// ---------------------------------------------------------------------------
function boundaryToGeoJSON() {
  if (boundaryPts.length < 3) return null;
  const ring = boundaryPts.map((p) => [p.x, p.y]);
  ring.push([boundaryPts[0].x, boundaryPts[0].y]);
  return { type: 'Polygon', coordinates: [ring] };
}
async function save() {
  setStatus('Saving\u2026');
  const payload = { venue_id: selectedVenueId, event: eventPayload(), venue: { area: boundaryToGeoJSON(), doors, power_outlets: power }, tables };
  log('save: committing venue', selectedVenueId, 'to event; layout written to that venue');
  try {
    const res = await fetch(boot.saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': boot.csrf }, body: JSON.stringify(payload) });
    if (!res.ok) { setStatus('Save failed'); log('save failed', res.status); return; }
    const saved = await res.json();
    boot.data = saved;
    selectedVenueId = saved.event.venue_id;
    log('saved. committed venue is now', selectedVenueId);
    loadData(saved);
    initEventForm();
    fitView();
    setStatus('Saved');
  } catch (err) { setStatus('Save failed'); console.error(err); }
}
function setStatus(text) { document.getElementById('status').textContent = text; }

function fitView() {
  if (boundaryPts.length < 2) { stage.scale({ x: 0.3, y: 0.3 }); stage.position({ x: 60, y: 60 }); return; }
  const xs = boundaryPts.map((p) => p.x), ys = boundaryPts.map((p) => p.y);
  const minX = Math.min(...xs), maxX = Math.max(...xs), minY = Math.min(...ys), maxY = Math.max(...ys);
  const pad = 120;
  const scale = Math.min(stage.width() / (maxX - minX + pad * 2), stage.height() / (maxY - minY + pad * 2));
  stage.scale({ x: scale, y: scale });
  stage.position({ x: -(minX - pad) * scale, y: -(minY - pad) * scale });
}
