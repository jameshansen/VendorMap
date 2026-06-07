<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_doors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('type')->default('entrance'); // entrance | exit | emergency | loading
            $table->float('x');                           // centre, cm in floor space
            $table->float('y');
            $table->float('width')->default(90);          // door opening width, cm
            $table->float('rotation')->default(0);        // degrees
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_doors');
    }
};
