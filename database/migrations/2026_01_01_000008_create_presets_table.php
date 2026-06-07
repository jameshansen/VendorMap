<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presets', function (Blueprint $table) {
            $table->id();
            $table->string('kind');     // table | door | power
            $table->string('name');
            $table->json('data');       // shape-specific fields (width, height, type, etc.)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presets');
    }
};
