<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Harga baru yang disiapkan di menu Set New Price (per item + supplier + periode harga baru). */
class NewPrice extends Model
{
    use Auditable;

    public const AuditModule = 'New Price';

    public const StatusDraft = 'DRAFT';

    protected $table = 'new_prices';
    protected $primaryKey = 'new_price_id';
    protected $guarded = [];

    protected $casts = [
        'current_price' => 'float',
        'new_price' => 'float',
        'current_tc_from' => 'date',
        'current_tc_to' => 'date',
        'new_tc_from' => 'date',
        'new_tc_to' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function auditLabel(): string
    {
        return $this->item_code . ' / ' . $this->supplier?->supplier_code;
    }
}
