// GeoJSON coordinates are [x, y] (longitude, latitude). On our local floor
// plan we use x = horizontal, y = vertical, both in centimetres.

export interface GeoPolygon {
  type: 'Polygon';
  coordinates: number[][][];
}

export interface GeoPoint {
  type: 'Point';
  coordinates: [number, number];
}

export type DoorType = 'entrance' | 'exit' | 'emergency' | 'loading';

export interface Door {
  id?: number;
  label?: string | null;
  type: DoorType;
  x: number;
  y: number;
  width: number;
  rotation: number;
}

export interface PowerOutlet {
  id?: number;
  label?: string | null;
  x: number;
  y: number;
  amperage?: number | null;
  voltage?: number | null;
  outlets: number;
}

export interface Venue {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  location: GeoPoint | null;
  area: GeoPolygon | null;
  doors: Door[];
  power_outlets: PowerOutlet[];
}

export type TableStatus = 'available' | 'held' | 'booked';

export interface EventTable {
  id?: number;
  label?: string | null;
  vendor_id?: number | null;
  x: number;
  y: number;
  width: number;
  height: number;
  rotation: number;
  shape: 'rect' | 'round';
  price: number;
  status: TableStatus;
  notes?: string | null;
}

export interface EventWithVenue {
  id: number;
  name: string;
  venue: Venue;
  tables: EventTable[];
}
