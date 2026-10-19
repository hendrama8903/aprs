<?php

namespace App\Http\Controllers;

use App\Models\OgsImport;
use App\Repositories\OgsImportRepo;
use App\Support\DBResult;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Upload file export OGS (item master / price master) dan riwayat import-nya.
 */
class OgsImportController extends Controller
{
    public function __construct(private OgsImportRepo $ogsImportRepo)
    {
    }

    public function index()
    {
        return view('ogs-import.index', [
            'upload' => $this->hasPermission(PermissionCodes::OgsImportUpload),
            'lastImports' => $this->ogsImportRepo->lastImports(),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->ogsImportRepo->search($request->input('importType')));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(
            fn () => $this->ogsImportRepo->getByKey((int) $request->input('importId')),
            'Import log not found.',
        );
    }

    public function downloadTemplate(Request $request)
    {
        $type = (string) $request->query('importType');
        if (!array_key_exists($type, OgsImport::TypeLabels)) {
            abort(404);
        }

        $writer = new Xlsx($this->ogsImportRepo->template($type));
        $fileName = 'Template_' . str_replace(' ', '_', OgsImport::TypeLabels[$type]) . '.xlsx';

        return response()->streamDownload(fn () => $writer->save('php://output'), $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function upload(Request $request)
    {
        return $this->runJson(function () use ($request) {
            if (!$this->hasPermission(PermissionCodes::OgsImportUpload)) {
                return new DBResult(false, 'You do not have permission to perform this action.');
            }

            $type = (string) $request->input('importType');
            if (!array_key_exists($type, OgsImport::TypeLabels)) {
                return new DBResult(false, 'Please choose the import type.');
            }

            $file = $request->file('file');
            if ($file === null || !$file->isValid()) {
                return new DBResult(false, 'Please choose a file to upload.');
            }
            if (!in_array(strtolower($file->getClientOriginalExtension()), ['xlsx', 'xls'], true)) {
                return new DBResult(false, 'Only Excel files (.xlsx / .xls) are allowed.');
            }
            if ($file->getSize() > 20 * 1024 * 1024) {
                return new DBResult(false, 'File is too large (max 20 MB).');
            }

            // File OGS bisa ribuan baris; PhpSpreadsheet butuh memori & waktu lebih dari default.
            ini_set('memory_limit', '1024M');
            set_time_limit(300);

            return $this->ogsImportRepo->import($type, $file->getRealPath(), $file->getClientOriginalName(), $this->loginUsername());
        });
    }
}
