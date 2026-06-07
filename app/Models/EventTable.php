<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single bookable table within an event's layout.
 * Position is the table centre in the venue's local coordinate space (centimetres).
 */
class EventTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'vendor_id',
        'label',
        'x',
        'y',
        'width',
        'height',
        'rotation',
        'shape',     // rect | round
        'price',
        'status',    // available | held | booked
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'width' => 'float',
            'height' => 'float',
            'rotation' => 'float',
            'price' => 'decimal:2',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
