<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kurs mata uang asing ke IDR (rate = IDR per 1 unit) selama valid_from - valid_to.
 */
class ExchangeRate extends Model
{
    protected $table = 'exchange_rates';
    protected $primaryKey = 'exchange_rate_id';
    protected $guarded = [];

    protected $casts = [
        'rate' => 'float',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];
}
