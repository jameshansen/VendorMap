<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'role' => 'admin']
        );

        // A 20m x 14m hall, outline drawn in centimetres (local SRID 0 space).
        // Point() takes (latitude, longitude); for a Cartesian plane we treat
        // latitude as Y and longitude as X. GeoJSON output is then [X, Y].
        $venue = Venue::firstOrCreate(
            ['slug' => 'demo-market-hall'],
            [
                'name' => 'Demo Market Hall',
                'description' => 'Sample venue created by the demo seeder.',
                'created_by' => $admin->id,
                'location' => new Point(49.2827, -123.1207, 4326), // Vancouver-ish
                'area' => new Polygon([
                    new LineString([
                        new Point(0, 0, 0),
                        new Point(0, 2000, 0),
                        new Point(1400, 2000, 0),
                        new Point(1400, 0, 0),
                        new Point(0, 0, 0),
                    ]),
                ], 0),
            ]
        );

        $venue->doors()->firstOrCreate(
            ['label' => 'Main entrance'],
            ['type' => 'entrance', 'x' => 0, 'y' => 1000, 'width' => 200, 'rotation' => 90]
        );
        $venue->doors()->firstOrCreate(
            ['label' => 'Fire exit'],
            ['type' => 'emergency', 'x' => 1400, 'y' => 1000, 'width' => 120, 'rotation' => 90]
        );

        $venue->powerOutlets()->firstOrCreate(
            ['label' => 'Wall drop A'],
            ['x' => 200, 'y' => 50, 'amperage' => 15, 'voltage' => 120, 'outlets' => 2]
        );

        $event = Event::firstOrCreate(
            ['slug' => 'demo-saturday-market'],
            [
                'venue_id' => $venue->id,
                'name' => 'Saturday Market',
                'status' => 'draft',
                'is_public' => true,
                'starts_at' => now()->addWeeks(2)->setTime(9, 0),
                'ends_at' => now()->addWeeks(2)->setTime(15, 0),
                'registration_opens_at' => now()->setTime(0, 0),
                'registration_closes_at' => now()->addWeeks(2)->subDays(2)->setTime(17, 0),
                'cancellation_deadline' => now()->addWeeks(2)->subDays(5)->setTime(17, 0),
            ]
        );

        if ($event->tables()->count() === 0) {
            $row = 0;
            foreach ([300, 600, 900, 1200] as $y) {
                $col = 0;
                foreach ([400, 700, 1000] as $x) {
                    $event->tables()->create([
                        'label' => chr(65 + $row) . ($col + 1),
                        'x' => $x,
                        'y' => $y,
                        'width' => 180,
                        'height' => 75,
                        'rotation' => 0,
                        'price' => 45.00,
                        'status' => 'available',
                    ]);
                    $col++;
                }
                $row++;
            }
        }

        // A couple of globally shared presets to demonstrate the palette.
        \App\Models\Preset::firstOrCreate(
            ['kind' => 'table', 'name' => 'Corner booth'],
            ['data' => ['width' => 220, 'height' => 90, 'shape' => 'rect']]
        );
        \App\Models\Preset::firstOrCreate(
            ['kind' => 'power', 'name' => 'Heavy 50A'],
            ['data' => ['amperage' => 50, 'voltage' => 240, 'outlets' => 1]]
        );
    }
}
