<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Support\AuditTrail;
use Illuminate\Support\Str;

/**
 * Catat create / update / delete model ke audit trail.
 * Model boleh mendefinisikan const AuditModule (nama modul) dan auditLabel() (identitas record yang mudah dibaca).
 * Catatan: query massal (Model::query()->update/delete/insert/upsert) tidak memicu event, catat manual bila perlu.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        foreach (['created' => AuditLog::ActionCreate, 'updated' => AuditLog::ActionUpdate, 'deleted' => AuditLog::ActionDelete] as $event => $action) {
            static::$event(fn (self $model) => AuditTrail::model($action, $model, $model->auditModule(), $model->auditLabel()));
        }
    }

    public function auditModule(): string
    {
        return defined(static::class . '::AuditModule') ? static::AuditModule : Str::headline(class_basename($this));
    }

    public function auditLabel(): string
    {
        return (string) $this->getKey();
    }
}
