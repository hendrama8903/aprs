<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AprDetail extends Model
{
    public $timestamps = false;

    protected $table = 'apr_details';
    protected $primaryKey = 'apr_detail_id';
    protected $guarded = [];

    protected $casts = [
        'qty' => 'float',
        'current_price' => 'float',
        'price_tc_from' => 'date',
        'price_tc_to' => 'date',
        'new_price' => 'float',
        'po_avg_price' => 'float',
        'po_min_price' => 'float',
        'po_max_price' => 'float',
        'po_last_price' => 'float',
        'po_last_date' => 'date',
    ];

    public function apr(): BelongsTo
    {
        return $this->belongsTo(Apr::class, 'apr_id', 'apr_id');
    }
}
