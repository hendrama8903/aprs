<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use Auditable;

    public const AuditModule = 'Supplier';

    protected $table = 'suppliers';
    protected $primaryKey = 'supplier_id';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class, 'supplier_id', 'supplier_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddress::class, 'supplier_id', 'supplier_id')
            ->orderByDesc('is_default')->orderBy('label');
    }

    public function auditLabel(): string
    {
        return $this->supplier_code . ' - ' . $this->supplier_name;
    }
}
