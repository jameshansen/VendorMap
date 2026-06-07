<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tables', function (Blueprint $table) {
            // Tables are now per event+venue so layouts can differ between venues.
            $table->foreignId('venue_id')
                ->nullable()
                ->after('event_id')
                ->constrained()
                ->nullOnDelete();
        });

        // Backfill existing rows: assign each table to the venue currently
        // committed to its event so nothing is orphaned.
        DB::statement('
            UPDATE event_tables et
            JOIN events e ON e.id = et.event_id
            SET et.venue_id = e.venue_id
            WHERE et.venue_id IS NULL
              AND e.venue_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('event_tables', function (Blueprint $table) {
            $table->dropForeign(['venue_id']);
            $table->dropColumn('venue_id');
        });
    }
};
