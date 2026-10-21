<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use Auditable;

    public const AuditModule = 'Menu';

    protected $table = 'menus';
    protected $primaryKey = 'menu_id';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_menu_id', 'menu_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_menu_id', 'menu_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'menu_id', 'menu_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu', 'menu_id', 'role_id');
    }

    public function auditLabel(): string
    {
        return (string) $this->menu_name;
    }
}
