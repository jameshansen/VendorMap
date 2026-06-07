<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->float('x');                        // centre, cm in floor space
            $table->float('y');
            $table->float('width')->default(180);      // cm (a 6ft trestle table)
            $table->float('height')->default(75);
            $table->float('rotation')->default(0);     // degrees
            $table->string('shape')->default('rect');  // rect | round
            $table->decimal('price', 8, 2)->default(0);
            $table->string('status')->default('available'); // available | held | booked
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_tables');
    }
};
