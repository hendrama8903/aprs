<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Alamat surat supplier; satu supplier bisa punya beberapa, satu di antaranya default. */
class SupplierAddress extends Model
{
    use Auditable;

    public const AuditModule = 'Supplier Address';

    protected $table = 'supplier_addresses';
    protected $primaryKey = 'address_id';
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function auditLabel(): string
    {
        return $this->supplier?->supplier_code . ' / ' . $this->label;
    }
}
