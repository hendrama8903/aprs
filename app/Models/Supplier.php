<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
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
}
