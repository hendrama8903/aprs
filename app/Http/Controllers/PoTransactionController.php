<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesOgsUpload;
use App\Models\OgsImport;
use App\Repositories\PoTransactionRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/**
 * Transaksi PO OGS: daftar (read-only), upload file export OGS, dan export Excel.
 */
class PoTransactionController extends Controller
{
    use HandlesOgsUpload;

    public function __construct(private PoTransactionRepo $poTransactionRepo)
    {
    }

    public function index()
    {
        return view('po-transaction.index', [
            'upload' => $this->hasPermission(PermissionCodes::PoTransactionUpload),
            'lastImport' => $this->lastOgsImport(OgsImport::TypePo),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->poTransactionRepo->search(...$this->filters($request)));
    }

    public function downloadTemplate()
    {
        return $this->ogsTemplate(OgsImport::TypePo);
    }

    public function upload(Request $request)
    {
        return $this->ogsUpload($request, OgsImport::TypePo, PermissionCodes::PoTransactionUpload);
    }

    public function importDetail(Request $request)
    {
        return $this->ogsImportDetail($request, OgsImport::TypePo);
    }

    public function downloadExcel(Request $request)
    {
        return $this->exportExcel(
            'PO Transaction',
            'PO-TRANSACTION',
            ['PO No', 'PO Line', 'PO Date', 'Item Code', 'Item Name', 'Unit', 'Supplier Code', 'Supplier Name', 'Currency', 'Qty', 'Unit Price', 'Amount'],
            $this->poTransactionRepo->search(...$this->filters($request)),
            fn (object $p) => [
                $p->PoNo, $p->PoLine, $p->PoDate?->format('d-m-Y'), $p->ItemCode, $p->ItemName, $p->Unit,
                $p->SupplierCode, $p->SupplierName, $p->Currency, $p->Qty, $p->UnitPrice, $p->Amount,
            ],
        );
    }

    /** @return array{0: ?int, 1: ?string, 2: ?string} supplier, PO date from, PO date to */
    private function filters(Request $request): array
    {
        return [
            $request->filled('supplierId') ? (int) $request->input('supplierId') : null,
            $request->input('dateFrom') ?: null,
            $request->input('dateTo') ?: null,
        ];
    }
}
