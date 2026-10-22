<?php

namespace App\Http\Controllers\Concerns;

use App\Models\OgsImport;
use App\Repositories\OgsImportRepo;
use App\Support\DBResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Upload file export OGS dari menu master masing-masing (Item, Item Price, PO Transaction).
 * Pasangannya di view: resources/views/partials/ogs-upload.blade.php.
 * Satu menu = satu jenis import (OgsImport::Type*); logika baca & validasi file ada di OgsImportRepo.
 */
trait HandlesOgsUpload
{
    protected function ogsTemplate(string $type): StreamedResponse
    {
        $writer = new Xlsx(app(OgsImportRepo::class)->template($type));
        $fileName = 'Template_' . str_replace(' ', '_', OgsImport::TypeLabels[$type]) . '.xlsx';

        return response()->streamDownload(fn () => $writer->save('php://output'), $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function ogsUpload(Request $request, string $type, string $permissionCode): JsonResponse
    {
        return $this->runJson(function () use ($request, $type, $permissionCode) {
            if (!$this->hasPermission($permissionCode)) {
                return new DBResult(false, 'You do not have permission to perform this action.');
            }

            $file = $request->file('file');
            if ($error = OgsImportRepo::validateUploadFile($file)) {
                return new DBResult(false, $error);
            }

            // File OGS bisa ribuan baris; PhpSpreadsheet butuh memori & waktu lebih dari default.
            ini_set('memory_limit', '1024M');
            set_time_limit(300);

            return app(OgsImportRepo::class)->import($type, $file->getRealPath(), $file->getClientOriginalName(), $this->loginUsername());
        });
    }

    /** Detail (warning) satu log upload; hanya untuk jenis milik menu ini. */
    protected function ogsImportDetail(Request $request, string $type): JsonResponse
    {
        return $this->jsonData(function () use ($request, $type) {
            $import = app(OgsImportRepo::class)->getByKey((int) $request->input('importId'));

            return $import !== null && $import->ImportType === $type ? $import : null;
        }, 'Import log not found.');
    }
}
