<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Dokumen Approval for Price Confirmation (header). */
class Apr extends Model
{
    use Auditable;

    public const AuditModule = 'APR';

    public const StatusDraft = 'DRAFT';
    public const StatusInApproval = 'IN_APPROVAL';
    public const StatusRejected = 'REJECTED';
    public const StatusApproved = 'APPROVED';
    public const StatusIssued = 'ISSUED';

    protected $table = 'aprs';
    protected $primaryKey = 'apr_id';
    protected $guarded = [];

    protected $casts = [
        'doc_date' => 'date',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'purchase_from' => 'date',
        'purchase_to' => 'date',
        'rate_usd_current' => 'float',
        'rate_usd_new' => 'float',
        'rate_jpy_current' => 'float',
        'rate_jpy_new' => 'float',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'prepared_by_user_id', 'user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(AprDetail::class, 'apr_id', 'apr_id')->orderBy('line_no');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AprApproval::class, 'apr_id', 'apr_id')->orderBy('apr_approval_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::StatusDraft, self::StatusRejected], true);
    }

    /** Kurs ke IDR untuk menghitung purchase amount; $new = pakai kurs baru. */
    public function rate(string $currency, bool $new): float
    {
        return match ($currency) {
            'USD' => $new ? $this->rate_usd_new : $this->rate_usd_current,
            'JPY' => $new ? $this->rate_jpy_new : $this->rate_jpy_current,
            default => 1.0,
        };
    }

    public function auditLabel(): string
    {
        return (string) $this->doc_no;
    }
}
