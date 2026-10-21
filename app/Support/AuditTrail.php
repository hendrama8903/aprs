<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Pencatat audit trail. Perubahan model ber-trait Auditable dicatat otomatis;
 * proses yang tidak lewat event model (upload OGS, akses role, login, dll.) memanggil log() langsung.
 */
final class AuditTrail
{
    /** Field teknis yang tidak dicatat sebagai perubahan. */
    private const IgnoredFields = ['created_at', 'created_by', 'updated_at', 'updated_by', 'last_login_at', 'remember_token'];

    /** Field rahasia: perubahannya dicatat, nilainya tidak. */
    private const MaskedFields = ['password_hash', 'password'];

    public static function log(
        string $action,
        string $module,
        ?string $recordKey = null,
        ?string $recordLabel = null,
        ?string $description = null,
        ?array $old = null,
        ?array $new = null,
        ?string $username = null,
    ): void {
        AuditLog::create([
            'log_date' => now(),
            'username' => mb_substr($username ?? self::currentUser(), 0, 50),
            'action' => $action,
            'module' => mb_substr($module, 0, 50),
            'record_key' => $recordKey === null ? null : mb_substr($recordKey, 0, 50),
            'record_label' => $recordLabel === null ? null : mb_substr($recordLabel, 0, 150),
            'description' => $description === null ? null : mb_substr($description, 0, 500),
            'old_values' => $old === null || $old === [] ? null : $old,
            'new_values' => $new === null || $new === [] ? null : $new,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    /** Dipanggil dari trait Auditable pada event created / updated / deleted. */
    public static function model(string $action, Model $model, string $module, string $label): void
    {
        [$old, $new] = match ($action) {
            AuditLog::ActionCreate => [null, self::clean($model->getAttributes())],
            AuditLog::ActionDelete => [self::clean($model->getRawOriginal()), null],
            default => self::diff($model),
        };
        if ($action === AuditLog::ActionUpdate && $new === []) {
            return; // hanya field teknis yang berubah
        }

        $verb = ['CREATE' => 'Created', 'UPDATE' => 'Updated', 'DELETE' => 'Deleted'][$action];
        $fields = $action === AuditLog::ActionUpdate ? ' (' . implode(', ', array_map(fn ($f) => Str::headline($f), array_keys($new))) . ')' : '';

        self::log($action, $module, (string) $model->getKey(), $label, "{$verb} {$module} {$label}{$fields}", $old, $new);
    }

    /** @return array{0: array, 1: array} nilai lama & baru dari field yang benar-benar berubah. */
    private static function diff(Model $model): array
    {
        $new = self::clean($model->getChanges());
        $old = [];
        foreach (array_keys($new) as $field) {
            $old[$field] = self::mask($field, $model->getRawOriginal($field));
        }

        return [$old, $new];
    }

    private static function clean(array $values): array
    {
        $result = [];
        foreach ($values as $field => $value) {
            if (!in_array($field, self::IgnoredFields, true)) {
                $result[$field] = self::mask($field, $value);
            }
        }

        return $result;
    }

    private static function mask(string $field, mixed $value): mixed
    {
        if (in_array($field, self::MaskedFields, true)) {
            return '********';
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
    }

    private static function currentUser(): string
    {
        if (app()->runningInConsole()) {
            return 'system';
        }

        return (string) (session(SessionKeys::Username) ?: 'guest');
    }
}
