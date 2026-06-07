<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A fixed door / entrance on the venue floor plan.
 * Position is stored in the venue's local coordinate space (centimetres).
 */
class VenueDoor extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'label',
        'type',      // entrance | exit | emergency | loading
        'x',
        'y',
        'width',
        'rotation',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'width' => 'float',
            'rotation' => 'float',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
