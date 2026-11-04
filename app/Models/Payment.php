<?php

namespace App\Models;

use App\Traits\BelongsToEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes, BelongsToEntity;

    protected $fillable = [
        'entity_id',
        'client_id',
        'project_id',
        'subscription_id',
        'type',
        'amount',
        'currency',
        'gateway',
        'gateway_payment_id',
        'gateway_customer_id',
        'status',
        'payment_link_token',
        'link_expires_at',
        'metadata',
        'paid_at',
        'refunded_at',
        'failure_reason',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'link_expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payment $payment) {
            if (!$payment->payment_link_token) {
                $payment->payment_link_token = Str::uuid()->toString();
                $payment->link_expires_at = now()->addDays(7);
            }
        });
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . strtoupper($this->currency ?? 'EUR');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentGatewayLog::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function linkExpired(): bool
    {
        return $this->link_expires_at && $this->link_expires_at->isPast();
    }
}
