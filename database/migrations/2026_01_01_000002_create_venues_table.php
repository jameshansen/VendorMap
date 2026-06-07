<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Geographic position of the building (where the market is on a map).
            $table->geometry('location', subtype: 'point', srid: 4326)->nullable();

            // Floor-plan outline drawn in the designer, in local centimetres.
            $table->geometry('area', subtype: 'polygon', srid: 0)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
