import { Routes } from '@angular/router';
import { Designer } from './designer/designer';

export const routes: Routes = [
  // The demo seeder creates venue 1 / event 1.
  { path: '', redirectTo: 'designer/1', pathMatch: 'full' },
  { path: 'designer/:eventId', component: Designer },
];
