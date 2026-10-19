<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoTransaction extends Model
{
    protected $table = 'po_transactions';
    protected $primaryKey = 'po_transaction_id';
    protected $guarded = [];

    protected $casts = [
        'po_date' => 'date',
        'qty' => 'float',
        'unit_price' => 'float',
        'amount' => 'float',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }
}
