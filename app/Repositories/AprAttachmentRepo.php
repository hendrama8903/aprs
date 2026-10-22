<?php

namespace App\Repositories;

use App\Models\Apr;
use App\Models\AprAttachment;
use App\Support\DBResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dokumen pendukung APR. Upload / hapus hanya selama dokumen masih bisa diedit (Draft / Rejected);
 * file disimpan di disk "local" per dokumen: apr-attachments/{apr_id}/{uuid}.{ext}.
 */
class AprAttachmentRepo
{
    public const Disk = 'local';

    public const Extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'txt', 'zip', 'msg', 'eml'];

    public const MaxFiles = 20;

    /** Batas ukuran per file (byte): yang terkecil dari config apr.attachment_max_mb dan batas upload PHP. */
    public static function maxBytes(): int
    {
        $toBytes = function (string $v): int {
            $v = trim($v);
            $n = (int) $v;

            return match (strtolower(substr($v, -1))) {
                'g' => $n * 1024 ** 3,
                'm' => $n * 1024 ** 2,
                'k' => $n * 1024,
                default => $n,
            };
        };
        $limits = array_filter([
            (int) config('apr.attachment_max_mb', 10) * 1024 ** 2,
            $toBytes((string) ini_get('upload_max_filesize')),
            $toBytes((string) ini_get('post_max_size')),
        ]);

        return min($limits);
    }

    /** @return object[] */
    public function list(int $aprId): array
    {
        return AprAttachment::query()->where('apr_id', $aprId)->orderBy('apr_attachment_id')->get()
            ->map(fn (AprAttachment $a) => $this->map($a))->all();
    }

    public function upload(int $aprId, ?UploadedFile $file, string $loginUser): DBResult
    {
        $apr = Apr::query()->find($aprId);
        if ($apr === null) {
            return new DBResult(false, 'Document not found.');
        }
        if (!$apr->isEditable()) {
            return new DBResult(false, "Document {$apr->doc_no} is already {$apr->status}; attachments can no longer be changed.");
        }
        if ($error = $this->validateFile($file)) {
            return new DBResult(false, $error);
        }
        if (AprAttachment::query()->where('apr_id', $aprId)->count() >= self::MaxFiles) {
            return new DBResult(false, 'A document can have at most ' . self::MaxFiles . ' attachments.');
        }

        $name = mb_substr(basename($file->getClientOriginalName()), 0, 255);
        $ext = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs("apr-attachments/{$aprId}", Str::uuid() . '.' . $ext, self::Disk);
        if ($path === false) {
            return new DBResult(false, "Failed to store {$name}. Please try again.");
        }

        try {
            $attachment = AprAttachment::create([
                'apr_id' => $aprId,
                'file_name' => $name,
                'stored_path' => $path,
                'mime_type' => mb_substr((string) $file->getClientMimeType(), 0, 100),
                'file_size' => $file->getSize(),
                'created_by' => $loginUser,
            ]);
        } catch (\Throwable $e) {
            Storage::disk(self::Disk)->delete($path);
            throw $e;
        }

        return new DBResult(true, "{$name} uploaded.", $attachment->apr_attachment_id);
    }

    public function delete(int $attachmentId): DBResult
    {
        $attachment = AprAttachment::query()->with('apr')->find($attachmentId);
        if ($attachment === null) {
            return new DBResult(false, 'Attachment not found. It may have been deleted by another user.');
        }
        if (!$attachment->apr->isEditable()) {
            return new DBResult(false, "Document {$attachment->apr->doc_no} is already {$attachment->apr->status}; attachments can no longer be changed.");
        }

        DB::transaction(fn () => $attachment->delete());
        Storage::disk(self::Disk)->delete($attachment->stored_path);

        return new DBResult(true, "{$attachment->file_name} deleted.");
    }

    public function find(int $attachmentId): ?AprAttachment
    {
        $attachment = AprAttachment::query()->find($attachmentId);

        return $attachment && Storage::disk(self::Disk)->exists($attachment->stored_path) ? $attachment : null;
    }

    /** Hapus semua file fisik satu dokumen (dipanggil saat dokumen dihapus; baris DB ikut terhapus lewat FK cascade). */
    public function deleteFiles(int $aprId): void
    {
        Storage::disk(self::Disk)->deleteDirectory("apr-attachments/{$aprId}");
    }

    private function validateFile(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return 'Please choose a file to upload. If you did, it may exceed the server upload limit of ' . $this->formatSize(self::maxBytes()) . '.';
        }
        if (!$file->isValid()) {
            return in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? "{$file->getClientOriginalName()} is larger than the limit of " . $this->formatSize(self::maxBytes()) . '.'
                : "{$file->getClientOriginalName()} failed to upload. Please try again.";
        }
        if (!in_array(strtolower($file->getClientOriginalExtension()), self::Extensions, true)) {
            return "{$file->getClientOriginalName()}: file type not allowed. Allowed: " . implode(', ', self::Extensions) . '.';
        }
        if ($file->getSize() > self::maxBytes()) {
            return "{$file->getClientOriginalName()} is larger than the limit of " . $this->formatSize(self::maxBytes()) . '.';
        }

        return null;
    }

    private function formatSize(int $bytes): string
    {
        return $bytes >= 1024 ** 2 ? round($bytes / 1024 ** 2, 1) . ' MB' : round($bytes / 1024) . ' KB';
    }

    private function map(AprAttachment $a): object
    {
        return (object) [
            'AttachmentId' => $a->apr_attachment_id,
            'FileName' => $a->file_name,
            'MimeType' => $a->mime_type,
            'FileSize' => $a->file_size,
            'CreatedBy' => $a->created_by,
            'CreatedDate' => $a->created_at,
        ];
    }
}
