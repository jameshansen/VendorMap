<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

/**
 * @property ?Point   $location  Geographic position of the venue (SRID 4326, lng/lat).
 * @property ?Polygon $area      Floor-plan outline in local centimetres (SRID 0).
 */
class Venue extends Model
{
    use HasFactory, HasSpatial;

    protected $fillable = [
        'created_by',
        'name',
        'slug',
        'description',
        'location',
        'area',
    ];

    protected function casts(): array
    {
        return [
            'location' => Point::class,
            'area' => Polygon::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function doors(): HasMany
    {
        return $this->hasMany(VenueDoor::class);
    }

    public function powerOutlets(): HasMany
    {
        return $this->hasMany(VenuePower::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
