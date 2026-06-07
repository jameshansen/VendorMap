<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('status');
            $table->dateTime('registration_opens_at')->nullable()->after('is_public');
            $table->dateTime('registration_closes_at')->nullable()->after('registration_opens_at');
            $table->dateTime('cancellation_deadline')->nullable()->after('registration_closes_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'registration_opens_at', 'registration_closes_at', 'cancellation_deadline']);
        });
    }
};
