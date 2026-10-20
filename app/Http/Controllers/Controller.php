<?php

namespace App\Http\Controllers;

use App\Support\DBResult;
use App\Support\SessionAuth;
use App\Support\SessionKeys;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Helper bersama controller CRUD, port dari Common/Controllers/BaseController.cs.
 */
abstract class Controller
{
    protected function loginUsername(): string
    {
        return session(SessionKeys::Username, '');
    }

    protected function loginUserId(): ?int
    {
        return session(SessionKeys::UserId);
    }

    protected function hasPermission(string $permissionCode): bool
    {
        return SessionAuth::hasPermission($permissionCode);
    }

    /** Set izin Add/Edit/Delete untuk tombol toolbar, mis. prefix "ITEM". */
    protected function crudPermissions(string $prefix): array
    {
        return [
            'add' => $this->hasPermission($prefix . '-ADD'),
            'edit' => $this->hasPermission($prefix . '-EDIT'),
            'delete' => $this->hasPermission($prefix . '-DELETE'),
        ];
    }

    protected function permissionDenied(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'You do not have permission to perform this action.']);
    }

    protected function jsonError(\Throwable $e): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $e->getMessage()]);
    }

    /** Hapus beberapa record sekaligus; lanjut walau ada yang gagal, lalu laporkan semua pesan gagal. */
    protected function deleteMany(string $permissionCode, array $ids, callable $delete): JsonResponse
    {
        try {
            if (!$this->hasPermission($permissionCode)) {
                return $this->permissionDenied();
            }

            $errors = [];
            foreach ($ids as $id) {
                $result = $delete((int) $id);
                if (!$result->status) {
                    $errors[] = $result->message;
                }
            }

            return $errors === []
                ? response()->json(['status' => true, 'message' => 'Data deleted successfully.'])
                : response()->json(['status' => false, 'message' => implode("\n", $errors)]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    /** Simpan ADD/EDIT dengan cek permission <prefix>-ADD / <prefix>-EDIT. */
    protected function saveByMode(string $screenMode, string $permissionPrefix, callable $insert, callable $update): JsonResponse
    {
        try {
            $isAdd = $screenMode === 'ADD';
            if (!$this->hasPermission($permissionPrefix . ($isAdd ? '-ADD' : '-EDIT'))) {
                return $this->permissionDenied();
            }

            return response()->json($isAdd ? $insert() : $update());
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    /** Jalankan satu aksi SP (Post/Cancel/...) dan kembalikan DBResult sebagai JSON. */
    protected function runJson(callable $action): JsonResponse
    {
        try {
            return response()->json($action());
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    /** Bungkus pemanggilan Search/GetByKey supaya format response seragam. */
    protected function jsonData(callable $load, string $notFoundMessage = 'Data not found.'): JsonResponse
    {
        try {
            $data = $load();

            return $data === null
                ? response()->json(['status' => false, 'message' => $notFoundMessage])
                : response()->json(['status' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    /**
     * Export list ke .xlsx. $map mengembalikan nilai sel sesuai urutan $headers.
     * Port dari BaseController.ExportExcel<T> (NPOI -> PhpSpreadsheet).
     *
     * @param object[] $rows
     */
    protected function exportExcel(string $sheetName, string $filePrefix, array $headers, iterable $rows, callable $map): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetName, 0, 31));

        $sheet->setCellValue('A1', 'No');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        foreach ($headers as $col => $header) {
            $cell = $sheet->getCell([$col + 2, 1]);
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true);
        }

        $rowIndex = 2;
        foreach ($rows as $item) {
            $sheet->setCellValue('A' . $rowIndex, $rowIndex - 1);

            $values = $map($item);
            foreach ($values as $col => $value) {
                $cell = $sheet->getCell([$col + 2, $rowIndex]);
                if ($value instanceof \DateTimeInterface) {
                    $cell->setValue($value->format('d-m-Y H:i'));
                } elseif (is_string($value) && preg_match('/^0\d+$/', $value)) {
                    // Kode berawalan nol (mis. supplier 0147) tetap teks, supaya bisa di-upload balik apa adanya.
                    $cell->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $cell->setValue($value);
                }
            }
            $rowIndex++;
        }

        foreach (range(1, count($headers) + 1) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = $filePrefix . '-' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
