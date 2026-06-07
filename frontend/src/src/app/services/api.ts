import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Door, EventTable, EventWithVenue, GeoPoint, GeoPolygon, PowerOutlet, Venue } from '../models/types';

interface VenueLayoutPayload {
  name?: string;
  area: GeoPolygon | null;
  location?: GeoPoint | null;
  doors: Door[];
  power_outlets: PowerOutlet[];
}

@Injectable({ providedIn: 'root' })
export class ApiService {
  private http = inject(HttpClient);
  private base = environment.apiBase;

  getEvent(eventId: number): Observable<EventWithVenue> {
    return this.http.get<EventWithVenue>(`${this.base}/events/${eventId}`);
  }

  getVenue(venueId: number): Observable<Venue> {
    return this.http.get<Venue>(`${this.base}/venues/${venueId}`);
  }

  // Saves the venue's fixed features: boundary, doors, power outlets.
  saveVenueLayout(venueId: number, payload: VenueLayoutPayload): Observable<Venue> {
    return this.http.put<Venue>(`${this.base}/venues/${venueId}`, payload);
  }

  // Replaces the full set of tables for an event.
  saveTables(eventId: number, tables: EventTable[]): Observable<EventTable[]> {
    return this.http.put<EventTable[]>(`${this.base}/events/${eventId}/tables`, { tables });
  }
}
