<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Dokumen pendukung APR; file fisik di disk "local" (stored_path). */
class AprAttachment extends Model
{
    use Auditable;

    public const AuditModule = 'APR Attachment';

    public const UPDATED_AT = null;

    protected $table = 'apr_attachments';
    protected $primaryKey = 'apr_attachment_id';
    protected $guarded = [];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function apr(): BelongsTo
    {
        return $this->belongsTo(Apr::class, 'apr_id', 'apr_id');
    }

    public function auditLabel(): string
    {
        return ($this->apr?->doc_no ?? '#' . $this->apr_id) . ' / ' . $this->file_name;
    }
}
