<?php

namespace App\Models;

use App\Traits\BelongsToEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, BelongsToEntity;

    protected $fillable = [
        'entity_id',
        'name',
        'email',
        'phone',
        'vat_number',
        'tax_office',
        'address',
        'city',
        'postal_code',
        'country',
        'company_name',
        'status',
        'source',
        'notes',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
