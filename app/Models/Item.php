<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use Auditable;

    public const AuditModule = 'Item';

    protected $table = 'items';
    protected $primaryKey = 'item_id';
    protected $guarded = [];

    protected $casts = [
        'tc_from' => 'date',
        'tc_to' => 'date',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class, 'item_id', 'item_id');
    }

    public function auditLabel(): string
    {
        return $this->item_code;
    }
}
