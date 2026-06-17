<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A vendor who books tables at events. Optionally linked to a User login.
 */
class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',           // pending | approved | rejected
        'business_name',
        'contact_name',
        'email',
        'phone',
        'address',
        'website',
        'socials',
        'categories',        // product categories the vendor carries (list of names)
        'application_note',  // applicant-supplied note for verification
        'admin_notes',       // internal, admin-only
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'socials' => 'array',
            'categories' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(EventTable::class);
    }
}
