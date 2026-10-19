<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Nomor dokumen berurutan per jenis & tahun, format dari config/apr.php (doc_no_format).
 * Harus dipanggil di dalam transaksi DB: counter dikunci (lockForUpdate) supaya tidak ada nomor ganda.
 */
final class DocNumber
{
    private const Roman = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /** @param string $type 'internal' | 'external' */
    public static function next(string $type, CarbonInterface $date): string
    {
        $key = strtoupper($type) . '-' . $date->format('Y');

        DB::table('doc_counters')->insertOrIgnore(['counter_key' => $key, 'last_no' => 0]);
        $last = (int) DB::table('doc_counters')->where('counter_key', $key)->lockForUpdate()->value('last_no');
        $no = $last + 1;
        DB::table('doc_counters')->where('counter_key', $key)->update(['last_no' => $no]);

        return strtr(config("apr.doc_no_format.{$type}"), [
            '{no}' => str_pad((string) $no, 3, '0', STR_PAD_LEFT),
            '{dept}' => config('apr.company.department'),
            '{roman}' => self::Roman[(int) $date->format('n')],
            '{yy}' => $date->format('y'),
        ]);
    }
}
