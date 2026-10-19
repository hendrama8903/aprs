<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AprApproval extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'apr_approvals';
    protected $primaryKey = 'apr_approval_id';
    protected $guarded = [];
}
