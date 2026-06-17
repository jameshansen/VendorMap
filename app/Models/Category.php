<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Admin-curated suggestion of a vendor product category (e.g. Candles, Scarves).
 * Vendors pick from these or type their own; the chosen names are stored on the
 * vendor as a JSON list rather than a pivot, so custom entries are allowed.
 */
class Category extends Model
{
    protected $fillable = ['name'];
}
