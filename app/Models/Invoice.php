<?php

namespace App\Models;

use App\Traits\BelongsToEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToEntity;

    protected $fillable = [
        'entity_id',
        'payment_id',
        'client_id',
        'project_id',
        'invoice_number',
        'series_code',
        'sequence_number',
        'fiscal_year',
        'issue_date',
        'document_type',
        'uid',
        'mark',
        'qr_url',
        'cancellation_mark',
        'transmitted_at',
        'cancelled_at',
        'line_items',
        'subtotal',
        'vat_amount',
        'total',
        'currency',
        'pdf_path',
        'status',
        'failure_message',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'transmitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'line_items' => 'array',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function aadeLogs(): HasMany
    {
        return $this->hasMany(AadeLog::class);
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued' && !empty($this->mark);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled(): bool
    {
        return $this->isIssued() && !$this->isCancelled();
    }
}
