<?php

namespace App\Support;

use JsonSerializable;

/**
 * Hasil standar stored procedure Insert/Update/Delete.
 * RETURN 1 = sukses, 0 = gagal, pesan dari parameter OUTPUT @ReturnMessage.
 * Port dari Common/Models/DBResult.cs.
 */
class DBResult implements JsonSerializable
{
    public function __construct(
        public bool $status,
        public string $message = '',
        public ?int $returnId = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'returnId' => $this->returnId,
        ];
    }
}
