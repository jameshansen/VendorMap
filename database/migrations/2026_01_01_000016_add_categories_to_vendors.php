<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // The products a vendor carries, as a free list of category names
            // (drawn from the admin suggestion list and/or custom entries).
            $table->json('categories')->nullable()->after('socials');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('categories');
        });
    }
};
