<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OgsImport extends Model
{
    public const TypeItem = 'ITEM';
    public const TypePrice = 'PRICE';

    public const UPDATED_AT = null;

    protected $table = 'ogs_imports';
    protected $primaryKey = 'import_id';
    protected $guarded = [];

    protected $casts = [
        'warnings' => 'array',
    ];
}
