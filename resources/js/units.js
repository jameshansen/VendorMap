// ---------------------------------------------------------------------------
// Unit display helper shared by the designer and booking views.
// Models always store centimetres; this only affects what the user sees/edits.
//   metric   -> small sizes in cm, large (room) sizes in m
//   imperial -> small sizes in inches, large (room) sizes in feet
// The default comes from config (passed in at boot); each browser can override
// it via a toggle, remembered in localStorage.
// ---------------------------------------------------------------------------
const KEY = 'vendormap.units';
const CM_PER_IN = 2.54;
const CM_PER_FT = 30.48;

let current = 'metric';

export function initUnits(defaultUnit) {
  const stored = localStorage.getItem(KEY);
  current = stored === 'metric' || stored === 'imperial'
    ? stored
    : (defaultUnit === 'imperial' ? 'imperial' : 'metric');
  return current;
}

export function currentUnit() { return current; }
export function isImperial() { return current === 'imperial'; }

export function setUnit(unit) {
  current = unit === 'imperial' ? 'imperial' : 'metric';
  localStorage.setItem(KEY, current);
  return current;
}

export function toggleUnit() { return setUnit(current === 'metric' ? 'imperial' : 'metric'); }

// ---- small lengths (table/door sizes): cm <-> in -------------------------
export function cmToSmall(cm) {
  return isImperial() ? round1(cm / CM_PER_IN) : Math.round(cm);
}
export function smallToCm(v) {
  const n = Number(v) || 0;
  return isImperial() ? Math.round(n * CM_PER_IN) : Math.round(n);
}
export function smallUnit() { return isImperial() ? 'in' : 'cm'; }

// ---- large lengths (room width/height): cm <-> m or ft -------------------
export function cmToBig(cm) {
  return isImperial() ? round1(cm / CM_PER_FT) : round1(cm / 100);
}
export function bigToCm(v) {
  const n = Number(v) || 0;
  return isImperial() ? Math.round(n * CM_PER_FT) : Math.round(n * 100);
}
export function bigUnit() { return isImperial() ? 'ft' : 'm'; }

// Pretty size string for a table, e.g. "180×75 cm" or "70.9×29.5 in (round)".
export function formatSize(wCm, hCm, round) {
  const w = cmToSmall(wCm);
  const h = cmToSmall(hCm);
  return `${w}×${h} ${smallUnit()}` + (round ? ' (round)' : '');
}

function round1(n) { return Math.round(n * 10) / 10; }
