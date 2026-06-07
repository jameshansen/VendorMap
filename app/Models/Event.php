<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An event held at a venue (a market day, a convention, etc.).
 * Tables are laid out per event so pricing and layout can change each time.
 */
class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'name',
        'slug',
        'description',
        'starts_at',
        'ends_at',
        'status',    // draft | published | closed
        'is_public',
        'registration_opens_at',
        'registration_closes_at',
        'cancellation_deadline',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_public' => 'boolean',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'cancellation_deadline' => 'datetime',
        ];
    }

    /** Whether the registration window is currently open for booking. */
    public function registrationOpen(): bool
    {
        $now = now();

        if ($this->registration_opens_at && $now->lt($this->registration_opens_at)) {
            return false;
        }

        if ($this->registration_closes_at && $now->gt($this->registration_closes_at)) {
            return false;
        }

        return true;
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(EventTable::class);
    }
}
