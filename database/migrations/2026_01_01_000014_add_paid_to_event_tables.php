<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tables', function (Blueprint $table) {
            // Payment is handled offline; an admin marks a booking paid in the panel.
            $table->boolean('paid')->default(false)->after('booked_at');
            $table->dateTime('paid_at')->nullable()->after('paid');
        });
    }

    public function down(): void
    {
        Schema::table('event_tables', function (Blueprint $table) {
            $table->dropColumn(['paid', 'paid_at']);
        });
    }
};
