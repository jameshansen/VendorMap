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
        'venue_id',
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
        'has_power', // electrical power available at this table
        'booked_at',
        'terms_accepted_at', // when the vendor agreed to the conditions at booking
        'paid',      // booking has been paid for (marked by an admin)
        'paid_at',
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
            'has_power' => 'boolean',
            'booked_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'paid' => 'boolean',
            'paid_at' => 'datetime',
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
