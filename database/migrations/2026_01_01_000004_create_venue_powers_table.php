<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_powers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->float('x');                              // centre, cm in floor space
            $table->float('y');
            $table->unsignedInteger('amperage')->nullable();
            $table->unsignedInteger('voltage')->nullable();
            $table->unsignedTinyInteger('outlets')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_powers');
    }
};
