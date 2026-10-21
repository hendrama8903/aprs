<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Nomor dokumen berurutan per jenis & tahun, format dari config/apr.php (doc_no_format).
 * Harus dipanggil di dalam transaksi DB: counter dikunci (lockForUpdate) supaya tidak ada nomor ganda.
 * Nomor awal per tahun bisa diatur di menu Document Numbering (DocCounterRepo).
 */
final class DocNumber
{
    public const Types = ['internal', 'external'];

    private const Roman = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /** @param string $type 'internal' | 'external' */
    public static function next(string $type, CarbonInterface $date): string
    {
        $key = self::counterKey($type, (int) $date->format('Y'));

        DB::table('doc_counters')->insertOrIgnore(['counter_key' => $key, 'last_no' => 0]);
        $last = (int) DB::table('doc_counters')->where('counter_key', $key)->lockForUpdate()->value('last_no');
        $no = $last + 1;
        DB::table('doc_counters')->where('counter_key', $key)->update(['last_no' => $no]);

        return self::format($type, $no, $date);
    }

    public static function counterKey(string $type, int $year): string
    {
        return strtoupper($type) . '-' . $year;
    }

    public static function format(string $type, int $no, CarbonInterface $date): string
    {
        return str_replace('{no}', str_pad((string) $no, 3, '0', STR_PAD_LEFT), self::pattern($type, $date));
    }

    /** Format dengan semua bagian terisi kecuali {no}. */
    public static function pattern(string $type, CarbonInterface $date): string
    {
        return strtr(config("apr.doc_no_format.{$type}"), [
            '{dept}' => config('apr.company.department'),
            '{roman}' => self::Roman[(int) $date->format('n')],
            '{yy}' => $date->format('y'),
        ]);
    }

    /** Nomor urut dari nomor dokumen jadi (kebalikan format()), null kalau tidak cocok dengan format. */
    public static function parse(string $type, string $docNo): ?int
    {
        $regex = strtr(preg_quote(config("apr.doc_no_format.{$type}"), '/'), [
            '\{no\}' => '(?<no>\d+)',
            '\{dept\}' => preg_quote((string) config('apr.company.department'), '/'),
            '\{roman\}' => '[IVX]+',
            '\{yy\}' => '\d{2}',
        ]);

        return preg_match('/^' . $regex . '$/', $docNo, $m) ? (int) $m['no'] : null;
    }
}
