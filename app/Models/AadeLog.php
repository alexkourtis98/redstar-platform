<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AadeLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'operation',
        'invoice_type',
        'mark',
        'uid',
        'request_payload',
        'response_payload',
        'status',
        'errors',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'errors' => 'array',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
