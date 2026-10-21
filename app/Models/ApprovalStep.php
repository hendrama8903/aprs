<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    use Auditable;

    public const AuditModule = 'Approval Setting';

    protected $table = 'approval_steps';
    protected $primaryKey = 'step_id';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'approver_user_id', 'user_id');
    }

    public function auditLabel(): string
    {
        return (string) $this->step_name;
    }
}
