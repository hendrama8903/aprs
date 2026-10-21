<?php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Support\AuditTrail;
use App\Support\DBResult;
use App\Support\DocNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Document Numbering: nomor urut terakhir per jenis dokumen & tahun (tabel doc_counters, dipakai DocNumber::next()).
 * Dipakai saat mulai memakai sistem, supaya penomoran melanjutkan nomor dokumen manual yang sudah ada.
 *
 * Nomor internal mengikuti tahun tanggal dokumen APR; nomor eksternal (surat) mengikuti tahun tanggal issue.
 */
class DocCounterRepo
{
    private const Labels = [
        'internal' => 'APR (Internal)',
        'external' => 'Price Confirmation Letter (External)',
    ];

    private const MaxNo = 99999;

    /**
     * Counter tahun ini dan tahun depan selalu tampil (walau belum pernah dipakai), plus tahun lain yang sudah ada.
     *
     * @return object[]
     */
    public function search(): array
    {
        $counters = DB::table('doc_counters')->get()->keyBy('counter_key');
        $years = [(int) now()->format('Y'), (int) now()->format('Y') + 1];
        foreach ($counters->keys() as $key) {
            if (preg_match('/-(\d{4})$/', $key, $m)) {
                $years[] = (int) $m[1];
            }
        }
        $years = array_unique($years);
        rsort($years);

        $rows = [];
        foreach ($years as $year) {
            foreach (DocNumber::Types as $type) {
                $rows[] = $this->mapCounter($type, $year, $counters[DocNumber::counterKey($type, $year)] ?? null);
            }
        }

        return $rows;
    }

    public function getByKey(string $counterKey): ?object
    {
        [$type, $year] = $this->parseKey($counterKey) ?? [null, null];
        if ($type === null) {
            return null;
        }

        return $this->mapCounter($type, $year, DB::table('doc_counters')->where('counter_key', $counterKey)->first());
    }

    public function update(array $data, string $loginUser): DBResult
    {
        [$type, $year] = $this->parseKey((string) ($data['CounterKey'] ?? '')) ?? [null, null];
        if ($type === null) {
            return new DBResult(false, 'Document numbering not found.');
        }
        $nextNo = filter_var($data['NextNo'] ?? null, FILTER_VALIDATE_INT);
        if ($nextNo === false || $nextNo < 1 || $nextNo > self::MaxNo) {
            return new DBResult(false, 'Next number must be a whole number between 1 and ' . number_format(self::MaxNo) . '.');
        }

        $key = DocNumber::counterKey($type, $year);

        return DB::transaction(function () use ($type, $year, $key, $nextNo, $loginUser) {
            DB::table('doc_counters')->insertOrIgnore(['counter_key' => $key, 'last_no' => 0]);
            $oldLastNo = (int) DB::table('doc_counters')->where('counter_key', $key)->lockForUpdate()->value('last_no');

            // Nomor yang sudah dipakai dokumen tidak boleh terulang (doc_no / ext_doc_no unik).
            [$usedNo, $usedDocNo] = $this->highestUsed($type, $year);
            if ($usedNo !== null && $nextNo <= $usedNo) {
                return new DBResult(false, sprintf(
                    'Number %d is already used in %d (latest: %s). Next number must be at least %d.',
                    $nextNo, $year, $usedDocNo, $usedNo + 1,
                ));
            }

            DB::table('doc_counters')->where('counter_key', $key)->update([
                'last_no' => $nextNo - 1,
                'updated_by' => $loginUser,
                'updated_at' => now(),
            ]);

            $message = sprintf('Next %s number for %d is %s.', self::Labels[$type], $year, DocNumber::format($type, $nextNo, $this->sampleDate($year)));
            if ($oldLastNo !== $nextNo - 1) {
                AuditTrail::log(AuditLog::ActionUpdate, 'Document Numbering', $key, self::Labels[$type] . ' ' . $year, $message,
                    ['next_no' => $oldLastNo + 1], ['next_no' => $nextNo]);
            }

            return new DBResult(true, $message);
        });
    }

    /** @return array{0: ?int, 1: ?string} nomor urut tertinggi yang sudah dipakai di tahun itu, dan nomor dokumennya. */
    private function highestUsed(string $type, int $year): array
    {
        [$column, $dateColumn] = $type === 'internal' ? ['doc_no', 'doc_date'] : ['ext_doc_no', 'issued_at'];

        $max = null;
        $maxDocNo = null;
        foreach (DB::table('aprs')->whereNotNull($column)->whereYear($dateColumn, $year)->pluck($column) as $docNo) {
            $no = DocNumber::parse($type, $docNo);
            if ($no !== null && ($max === null || $no > $max)) {
                [$max, $maxDocNo] = [$no, $docNo];
            }
        }

        return [$max, $maxDocNo];
    }

    /** @return array{0: string, 1: int}|null */
    private function parseKey(string $counterKey): ?array
    {
        if (!preg_match('/^(INTERNAL|EXTERNAL)-(\d{4})$/', $counterKey, $m)) {
            return null;
        }

        return [strtolower($m[1]), (int) $m[2]];
    }

    /** Tanggal contoh untuk bulan romawi di preview: hari ini kalau tahun berjalan, selain itu Januari. */
    private function sampleDate(int $year): Carbon
    {
        return (int) now()->format('Y') === $year ? now() : Carbon::create($year, 1, 1);
    }

    private function mapCounter(string $type, int $year, ?object $counter): object
    {
        $lastNo = (int) ($counter->last_no ?? 0);
        $date = $this->sampleDate($year);

        return (object) [
            'CounterKey' => DocNumber::counterKey($type, $year),
            'DocType' => self::Labels[$type],
            'Year' => $year,
            'LastNo' => $lastNo,
            'NextNo' => $lastNo + 1,
            'NextDocNo' => DocNumber::format($type, $lastNo + 1, $date),
            // {no} diganti di form untuk preview saat Next Number diketik.
            'Pattern' => DocNumber::pattern($type, $date),
            'ModifiedBy' => $counter->updated_by ?? null,
            'ModifiedDate' => isset($counter->updated_at) ? Carbon::parse($counter->updated_at) : null,
        ];
    }
}
