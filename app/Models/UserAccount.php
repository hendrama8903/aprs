<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAccount extends Model
{
    protected $table = 'user_accounts';
    protected $primaryKey = 'user_id';
    protected $guarded = [];
    protected $hidden = ['password_hash'];

    protected $casts = [
        'is_ad_user' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'supervisor_user_id', 'user_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(UserAccount::class, 'supervisor_user_id', 'user_id');
    }
}
