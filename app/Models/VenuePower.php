<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A fixed power outlet / drop on the venue floor plan.
 * Position is stored in the venue's local coordinate space (centimetres).
 */
class VenuePower extends Model
{
    use HasFactory;

    protected $table = 'venue_powers';

    protected $fillable = [
        'venue_id',
        'label',
        'x',
        'y',
        'amperage',
        'voltage',
        'outlets',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'amperage' => 'integer',
            'voltage' => 'integer',
            'outlets' => 'integer',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
