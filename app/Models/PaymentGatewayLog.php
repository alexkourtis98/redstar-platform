<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'gateway',
        'event_type',
        'request_payload',
        'response_payload',
        'webhook_payload',
        'idempotency_key',
        'status',
        'error_message',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'webhook_payload' => 'array',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
