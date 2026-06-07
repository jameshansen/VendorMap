<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tables', function (Blueprint $table) {
            // Whether this table has an electrical power supply (set in the designer).
            $table->boolean('has_power')->default(false)->after('status');
            // When a vendor's booking was placed (audit / cancellation window).
            $table->dateTime('booked_at')->nullable()->after('has_power');
        });
    }

    public function down(): void
    {
        Schema::table('event_tables', function (Blueprint $table) {
            $table->dropColumn(['has_power', 'booked_at']);
        });
    }
};
