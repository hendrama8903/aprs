<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Kurs mata uang asing ke IDR (rate = IDR per 1 unit) selama valid_from - valid_to.
 */
class ExchangeRate extends Model
{
    use Auditable;

    public const AuditModule = 'Exchange Rate';

    protected $table = 'exchange_rates';
    protected $primaryKey = 'exchange_rate_id';
    protected $guarded = [];

    protected $casts = [
        'rate' => 'float',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function auditLabel(): string
    {
        return $this->currency . ' ' . $this->valid_from?->format('d-m-Y');
    }
}
