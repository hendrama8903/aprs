<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris price master. Berlaku selama Time Control (tc_from - tc_to); tidak ada flag aktif.
 * Berasal dari upload OGS (import_id terisi) atau input manual di menu Item Price (import_id null).
 */
class ItemPrice extends Model
{
    // Status harga terhadap satu tanggal acuan (tanggal dokumen APR, atau hari ini).
    public const StatusValid = 'VALID';
    public const StatusExpired = 'EXPIRED';
    public const StatusNotYetValid = 'NOT_YET_VALID';

    public const StatusLabels = [
        self::StatusValid => 'Valid',
        self::StatusExpired => 'Expired',
        self::StatusNotYetValid => 'Not yet valid',
    ];

    protected $table = 'item_prices';
    protected $primaryKey = 'item_price_id';
    protected $guarded = [];

    protected $casts = [
        'price' => 'float',
        'tc_from' => 'date',
        'tc_to' => 'date',
        'ogs_input_date' => 'datetime',
        'ogs_update_date' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    /** Harga yang Time Control-nya mencakup tanggal $date (Y-m-d). */
    public function scopeValidOn(Builder $query, string $date): Builder
    {
        return $query->where('tc_from', '<=', $date)->where('tc_to', '>=', $date);
    }

    public static function statusOn(?CarbonInterface $from, ?CarbonInterface $to, CarbonInterface $date): ?string
    {
        if ($from === null || $to === null) {
            return null;
        }

        return match (true) {
            $to->lt($date->copy()->startOfDay()) => self::StatusExpired,
            $from->gt($date->copy()->startOfDay()) => self::StatusNotYetValid,
            default => self::StatusValid,
        };
    }
}
