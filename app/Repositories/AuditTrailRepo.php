<?php

namespace App\Repositories;

use App\Models\AuditLog;
use Illuminate\Support\Str;

/**
 * Audit trail (read-only): daftar perubahan data & aktivitas login, difilter per rentang tanggal.
 */
class AuditTrailRepo
{
    /** Rentang tanggal maksimal sekali tampil, supaya grid (filter di client) tetap ringan. */
    public const MaxDays = 366;

    /** @return object[] */
    public function search(string $dateFrom, string $dateTo, ?string $module, ?string $action): array
    {
        return $this->query($dateFrom, $dateTo, $module, $action)
            ->get()
            ->map(fn (AuditLog $l) => $this->mapLog($l))
            ->all();
    }

    public function getByKey(int $auditId): ?object
    {
        $log = AuditLog::query()->find($auditId);
        if ($log === null) {
            return null;
        }

        $data = $this->mapLog($log);
        $data->Changes = $this->changes($log);

        return $data;
    }

    /** Untuk export Excel: baris log + ringkasan perubahan satu teks per baris. */
    public function export(string $dateFrom, string $dateTo, ?string $module, ?string $action): array
    {
        return $this->query($dateFrom, $dateTo, $module, $action)
            ->get()
            ->map(function (AuditLog $l) {
                $row = $this->mapLog($l);
                $row->ChangesText = implode("\n", array_map(
                    fn ($c) => $c->Field . ': ' . ($c->Old ?? '-') . ' -> ' . ($c->New ?? '-'),
                    $this->changes($l),
                ));

                return $row;
            })
            ->all();
    }

    /** @return string[] nama modul yang pernah tercatat, untuk dropdown filter. */
    public function modules(): array
    {
        return AuditLog::query()->distinct()->orderBy('module')->pluck('module')->all();
    }

    /** @return string|null pesan error kalau rentang tanggal tidak valid. */
    public function validateRange(string $dateFrom, string $dateTo): ?string
    {
        $from = \DateTime::createFromFormat('!Y-m-d', $dateFrom);
        $to = \DateTime::createFromFormat('!Y-m-d', $dateTo);
        if ($from === false || $to === false) {
            return 'Please fill the date range.';
        }
        if ($from > $to) {
            return 'Date from must not be later than date to.';
        }
        if ($from->diff($to)->days >= self::MaxDays) {
            return 'Date range can be at most ' . self::MaxDays . ' days.';
        }

        return null;
    }

    private function query(string $dateFrom, string $dateTo, ?string $module, ?string $action)
    {
        return AuditLog::query()
            ->where('log_date', '>=', $dateFrom . ' 00:00:00')
            ->where('log_date', '<=', $dateTo . ' 23:59:59')
            ->when($module, fn ($q) => $q->where('module', $module))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->orderByDesc('log_date')
            ->orderByDesc('audit_id');
    }

    /** @return object[] [{Field, Old, New}] gabungan field di old_values & new_values. */
    private function changes(AuditLog $log): array
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];
        $text = fn ($v) => match (true) {
            $v === null => null,
            is_bool($v) => $v ? 'Yes' : 'No',
            is_array($v) => implode(', ', $v),
            // Desimal dari database ("17000.0000") disamakan dengan input form ("17000").
            is_string($v) && preg_match('/^-?\d+\.\d+$/', $v) === 1 => rtrim(rtrim($v, '0'), '.'),
            default => (string) $v,
        };

        $rows = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
            $o = $text($old[$field] ?? null);
            $n = $text($new[$field] ?? null);
            if ($o === $n && array_key_exists($field, $old) && array_key_exists($field, $new)) {
                continue; // tidak berubah (mis. daftar permission role yang sama)
            }
            $rows[] = (object) ['Field' => Str::headline($field), 'Old' => $o, 'New' => $n];
        }

        return $rows;
    }

    private function mapLog(AuditLog $l): object
    {
        return (object) [
            'AuditId' => $l->audit_id,
            'LogDate' => $l->log_date,
            'Username' => $l->username,
            'Action' => $l->action,
            'Module' => $l->module,
            'RecordKey' => $l->record_key,
            'RecordLabel' => $l->record_label,
            'Description' => $l->description,
            'ChangeCount' => count($this->changes($l)),
            'IpAddress' => $l->ip_address,
        ];
    }
}
