import { Component, ElementRef, OnInit, inject, signal, viewChild } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from '../services/api';
import { Door, DoorType, EventTable, GeoPolygon, PowerOutlet, TableStatus } from '../models/types';

type Tool = 'select' | 'boundary' | 'door' | 'power' | 'table';
type SelKind = 'table' | 'door' | 'power' | 'vertex';
interface Selection { kind: SelKind; index: number; }
interface Vertex { x: number; y: number; }
interface View { x: number; y: number; w: number; h: number; }

@Component({
  selector: 'app-designer',
  standalone: true,
  templateUrl: './designer.html',
  styleUrl: './designer.css',
})
export class Designer implements OnInit {
  private route = inject(ActivatedRoute);
  private api = inject(ApiService);

  svg = viewChild<ElementRef<SVGSVGElement>>('svg');

  // --- state ---------------------------------------------------------------
  eventId = 0;
  venueId = 0;
  venueName = signal('');

  tool = signal<Tool>('select');
  boundary = signal<Vertex[]>([]);
  doors = signal<Door[]>([]);
  power = signal<PowerOutlet[]>([]);
  tables = signal<EventTable[]>([]);
  selected = signal<Selection | null>(null);

  view = signal<View>({ x: -200, y: -200, w: 1800, h: 2400 });
  status = signal<string>('Loading…');

  // transient interaction state (not reactive)
  private dragging: { kind: SelKind; index: number; start: Vertex; orig: Vertex } | null = null;
  private panning: { sx: number; sy: number; start: View } | null = null;

  // --- lifecycle -----------------------------------------------------------
  ngOnInit(): void {
    this.eventId = Number(this.route.snapshot.paramMap.get('eventId'));
    this.api.getEvent(this.eventId).subscribe({
      next: (ev) => {
        this.venueId = ev.venue.id;
        this.venueName.set(ev.venue.name);
        this.boundary.set(this.polygonToVertices(ev.venue.area));
        this.doors.set(ev.venue.doors ?? []);
        this.power.set(ev.venue.power_outlets ?? []);
        this.tables.set(ev.tables ?? []);
        this.fitView();
        this.status.set('Ready');
      },
      error: () => this.status.set('Could not load event. Is the API running?'),
    });
  }

  // --- coordinate helpers --------------------------------------------------
  viewBox = () => {
    const v = this.view();
    return `${v.x} ${v.y} ${v.w} ${v.h}`;
  };

  private toWorld(ev: PointerEvent | WheelEvent): Vertex {
    const el = this.svg()!.nativeElement;
    const ctm = el.getScreenCTM();
    if (!ctm) return { x: 0, y: 0 };
    const pt = new DOMPoint(ev.clientX, ev.clientY).matrixTransform(ctm.inverse());
    return { x: pt.x, y: pt.y };
  }

  private polygonToVertices(area: GeoPolygon | null): Vertex[] {
    if (!area?.coordinates?.length) return [];
    const ring = [...area.coordinates[0]];
    // Drop the closing point so editing has one handle per corner.
    if (ring.length > 1) {
      const first = ring[0];
      const last = ring[ring.length - 1];
      if (first[0] === last[0] && first[1] === last[1]) ring.pop();
    }
    return ring.map((c) => ({ x: c[0], y: c[1] }));
  }

  private verticesToPolygon(): GeoPolygon | null {
    const verts = this.boundary();
    if (verts.length < 3) return null;
    const ring = verts.map((v) => [v.x, v.y]);
    ring.push([verts[0].x, verts[0].y]); // close
    return { type: 'Polygon', coordinates: [ring] };
  }

  boundaryPoints = () => this.boundary().map((v) => `${v.x},${v.y}`).join(' ');

  private fitView(): void {
    const verts = this.boundary();
    if (verts.length < 2) return;
    const xs = verts.map((v) => v.x);
    const ys = verts.map((v) => v.y);
    const minX = Math.min(...xs), maxX = Math.max(...xs);
    const minY = Math.min(...ys), maxY = Math.max(...ys);
    const pad = Math.max(maxX - minX, maxY - minY) * 0.12 + 100;
    this.view.set({ x: minX - pad, y: minY - pad, w: (maxX - minX) + pad * 2, h: (maxY - minY) + pad * 2 });
  }

  // --- pointer interaction -------------------------------------------------
  onCanvasDown(ev: PointerEvent): void {
    const w = this.toWorld(ev);
    const t = this.tool();
    if (t === 'select') {
      this.selected.set(null);
      this.panning = { sx: ev.clientX, sy: ev.clientY, start: { ...this.view() } };
      this.svg()!.nativeElement.setPointerCapture(ev.pointerId);
    } else if (t === 'boundary') {
      this.boundary.update((b) => [...b, { x: Math.round(w.x), y: Math.round(w.y) }]);
    } else if (t === 'door') {
      this.doors.update((d) => [...d, { type: 'entrance', label: null, x: Math.round(w.x), y: Math.round(w.y), width: 90, rotation: 0 }]);
      this.selected.set({ kind: 'door', index: this.doors().length - 1 });
    } else if (t === 'power') {
      this.power.update((p) => [...p, { label: null, x: Math.round(w.x), y: Math.round(w.y), amperage: 15, voltage: 120, outlets: 2 }]);
      this.selected.set({ kind: 'power', index: this.power().length - 1 });
    } else if (t === 'table') {
      this.tables.update((tb) => [...tb, { label: this.nextTableLabel(), x: Math.round(w.x), y: Math.round(w.y), width: 180, height: 75, rotation: 0, shape: 'rect', price: 0, status: 'available' }]);
      this.selected.set({ kind: 'table', index: this.tables().length - 1 });
    }
  }

  onElementDown(ev: PointerEvent, kind: SelKind, index: number): void {
    ev.stopPropagation();
    this.selected.set({ kind, index });
    if (this.tool() !== 'select') return;
    const orig = this.posOf(kind, index);
    this.dragging = { kind, index, start: this.toWorld(ev), orig: { ...orig } };
    this.svg()!.nativeElement.setPointerCapture(ev.pointerId);
  }

  onMove(ev: PointerEvent): void {
    if (this.dragging) {
      const w = this.toWorld(ev);
      const dx = w.x - this.dragging.start.x;
      const dy = w.y - this.dragging.start.y;
      const nx = Math.round(this.dragging.orig.x + dx);
      const ny = Math.round(this.dragging.orig.y + dy);
      this.setPos(this.dragging.kind, this.dragging.index, nx, ny);
    } else if (this.panning) {
      const el = this.svg()!.nativeElement;
      const rect = el.getBoundingClientRect();
      const v = this.panning.start;
      const dx = (ev.clientX - this.panning.sx) * (v.w / rect.width);
      const dy = (ev.clientY - this.panning.sy) * (v.h / rect.height);
      this.view.set({ ...v, x: v.x - dx, y: v.y - dy });
    }
  }

  onUp(ev: PointerEvent): void {
    this.dragging = null;
    this.panning = null;
    try { this.svg()!.nativeElement.releasePointerCapture(ev.pointerId); } catch { /* ignore */ }
  }

  onWheel(ev: WheelEvent): void {
    ev.preventDefault();
    const factor = ev.deltaY > 0 ? 1.1 : 1 / 1.1;
    const w = this.toWorld(ev);
    const v = this.view();
    this.view.set({
      x: w.x - (w.x - v.x) * factor,
      y: w.y - (w.y - v.y) * factor,
      w: v.w * factor,
      h: v.h * factor,
    });
  }

  zoom(factor: number): void {
    const v = this.view();
    const cx = v.x + v.w / 2;
    const cy = v.y + v.h / 2;
    this.view.set({ x: cx - (v.w * factor) / 2, y: cy - (v.h * factor) / 2, w: v.w * factor, h: v.h * factor });
  }

  // --- element position get/set -------------------------------------------
  private posOf(kind: SelKind, i: number): Vertex {
    switch (kind) {
      case 'table': return this.tables()[i];
      case 'door': return this.doors()[i];
      case 'power': return this.power()[i];
      case 'vertex': return this.boundary()[i];
    }
  }

  private setPos(kind: SelKind, i: number, x: number, y: number): void {
    if (kind === 'table') this.tables.update((a) => a.map((t, j) => (j === i ? { ...t, x, y } : t)));
    else if (kind === 'door') this.doors.update((a) => a.map((t, j) => (j === i ? { ...t, x, y } : t)));
    else if (kind === 'power') this.power.update((a) => a.map((t, j) => (j === i ? { ...t, x, y } : t)));
    else if (kind === 'vertex') this.boundary.update((a) => a.map((t, j) => (j === i ? { x, y } : t)));
  }

  private nextTableLabel(): string {
    return 'T' + (this.tables().length + 1);
  }

  // --- property editing (called from the panel) ---------------------------
  patchTable(i: number, patch: Partial<EventTable>): void {
    this.tables.update((a) => a.map((t, j) => (j === i ? { ...t, ...patch } : t)));
  }
  patchDoor(i: number, patch: Partial<Door>): void {
    this.doors.update((a) => a.map((t, j) => (j === i ? { ...t, ...patch } : t)));
  }
  patchPower(i: number, patch: Partial<PowerOutlet>): void {
    this.power.update((a) => a.map((t, j) => (j === i ? { ...t, ...patch } : t)));
  }

  deleteSelected(): void {
    const sel = this.selected();
    if (!sel) return;
    if (sel.kind === 'table') this.tables.update((a) => a.filter((_, j) => j !== sel.index));
    else if (sel.kind === 'door') this.doors.update((a) => a.filter((_, j) => j !== sel.index));
    else if (sel.kind === 'power') this.power.update((a) => a.filter((_, j) => j !== sel.index));
    else if (sel.kind === 'vertex') this.boundary.update((a) => a.filter((_, j) => j !== sel.index));
    this.selected.set(null);
  }

  // typed accessors for the template
  selectedTable = () => { const s = this.selected(); return s?.kind === 'table' ? this.tables()[s.index] : null; };
  selectedDoor = () => { const s = this.selected(); return s?.kind === 'door' ? this.doors()[s.index] : null; };
  selectedPower = () => { const s = this.selected(); return s?.kind === 'power' ? this.power()[s.index] : null; };

  isSelected(kind: SelKind, index: number): boolean {
    const s = this.selected();
    return !!s && s.kind === kind && s.index === index;
  }

  // small helpers for template input events
  num(ev: Event): number { return Number((ev.target as HTMLInputElement).value); }
  str(ev: Event): string { return (ev.target as HTMLInputElement | HTMLSelectElement).value; }
  money(n: number): string { return '$' + Number(n || 0).toFixed(2); }
  anyStatus(v: string): TableStatus { return v as TableStatus; }
  anyDoorType(v: string): DoorType { return v as DoorType; }

  // --- save ----------------------------------------------------------------
  save(): void {
    this.status.set('Saving…');
    this.api.saveVenueLayout(this.venueId, {
      name: this.venueName(),
      area: this.verticesToPolygon(),
      doors: this.doors(),
      power_outlets: this.power(),
    }).subscribe({
      next: (venue) => {
        // adopt server ids so the next save updates instead of duplicating
        this.doors.set(venue.doors ?? []);
        this.power.set(venue.power_outlets ?? []);
        this.api.saveTables(this.eventId, this.tables()).subscribe({
          next: (tables) => { this.tables.set(tables); this.status.set('Saved'); },
          error: () => this.status.set('Tables failed to save'),
        });
      },
      error: () => this.status.set('Venue failed to save'),
    });
  }
}
