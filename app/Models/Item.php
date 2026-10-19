<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $table = 'items';
    protected $primaryKey = 'item_id';
    protected $guarded = [];

    protected $casts = [
        'tc_from' => 'date',
        'tc_to' => 'date',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class, 'item_id', 'item_id');
    }
}
