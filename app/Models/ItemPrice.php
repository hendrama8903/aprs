<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPrice extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'item_prices';
    protected $primaryKey = 'item_price_id';
    protected $guarded = [];

    protected $casts = [
        'price' => 'float',
        'tc_from' => 'date',
        'tc_to' => 'date',
        'ogs_input_date' => 'datetime',
        'ogs_update_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }
}
