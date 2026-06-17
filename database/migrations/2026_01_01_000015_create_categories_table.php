<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Admin-curated suggestion list of vendor product categories. */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Seed a sensible default set typical of a public market. Admins can
        // add/remove these; vendors may also type their own custom categories.
        $now = now();
        $defaults = [
            'Baked goods', 'Produce', 'Candles', 'Decorations', 'Scarves',
            'Jewellery', 'Clothing', 'Art', 'Pottery', 'Soap', 'Plants',
            'Honey', 'Preserves', 'Woodwork', 'Coffee & tea',
        ];
        DB::table('categories')->insert(array_map(
            fn ($name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now],
            $defaults
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
