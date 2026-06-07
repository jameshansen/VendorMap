<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A reusable object preset (a table size, a door type, a power spec) shared by
 * everyone using the designer.
 */
class Preset extends Model
{
    protected $fillable = ['kind', 'name', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
