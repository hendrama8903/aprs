<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris audit trail (lihat App\Support\AuditTrail). Hanya ditambah, tidak pernah diubah.
 */
class AuditLog extends Model
{
    public const ActionCreate = 'CREATE';
    public const ActionUpdate = 'UPDATE';
    public const ActionDelete = 'DELETE';
    public const ActionUpload = 'UPLOAD';
    public const ActionLogin = 'LOGIN';
    public const ActionLoginFailed = 'LOGIN FAILED';
    public const ActionLogout = 'LOGOUT';

    public $timestamps = false;

    protected $table = 'audit_logs';
    protected $primaryKey = 'audit_id';
    protected $guarded = [];

    protected $casts = [
        'log_date' => 'datetime',
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}
