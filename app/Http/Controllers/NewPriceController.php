<?php

namespace App\Http\Controllers;

use App\Repositories\NewPriceRepo;
use App\Repositories\OgsImportRepo;
use App\Support\DBResult;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/**
 * Set New Price: lembar kerja harga baru per periode. Current price dari price master yang Valid di awal periode;
 * new price diisi di grid, lewat Adjust %, atau upload file hasil Export Excel.
 */
class NewPriceController extends Controller
{
    public function __construct(private NewPriceRepo $newPriceRepo)
    {
    }

    public function index()
    {
        return view('new-price.index', [
            'edit' => $this->hasPermission(PermissionCodes::NewPriceEdit),
            'threshold' => config('apr.remark_threshold_pct'),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->newPriceRepo->worksheet(...$this->filters($request)));
    }

    public function save(Request $request)
    {
        return $this->runJson(function () use ($request) {
            if (!$this->hasPermission(PermissionCodes::NewPriceEdit)) {
                return new DBResult(false, 'You do not have permission to perform this action.');
            }
            $rows = json_decode((string) $request->input('rows', '[]'), true);

            return $this->newPriceRepo->save(
                (string) $request->input('dateFrom'),
                (string) $request->input('dateTo'),
                is_array($rows) ? $rows : [],
                $this->loginUsername(),
            );
        });
    }

    public function upload(Request $request)
    {
        return $this->runJson(function () use ($request) {
            if (!$this->hasPermission(PermissionCodes::NewPriceEdit)) {
                return new DBResult(false, 'You do not have permission to perform this action.');
            }
            $file = $request->file('file');
            if ($error = OgsImportRepo::validateUploadFile($file)) {
                return new DBResult(false, $error);
            }

            return $this->newPriceRepo->upload(
                (string) $request->input('dateFrom'),
                (string) $request->input('dateTo'),
                $file->getRealPath(),
                $this->loginUsername(),
            );
        });
    }

    public function downloadExcel(Request $request)
    {
        [$from, $to] = $this->filters($request);

        // Kolom ITEM CODE, SUPPLIER CODE, NEW PRICE, REMARK dibaca kembali oleh Upload.
        return $this->exportExcel(
            'New Price ' . $from,
            'NEW-PRICE',
            ['Item Code', 'Item Name', 'Unit', 'Supplier Code', 'Supplier Name', 'Plant', 'Currency',
                'Current Price', 'Current TC From', 'Current TC To', 'Quotation', 'New Price', 'Remark', 'Movement %', 'Status'],
            $this->newPriceRepo->worksheet(...$this->filters($request)),
            fn (object $r) => [
                $r->ItemCode, $r->ItemName, $r->Unit, $r->SupplierCode, $r->SupplierName, $r->PlantCode, $r->Currency,
                $r->CurrentPrice, $r->CurrentTcFrom, $r->CurrentTcTo, $r->Quotation, $r->NewPrice, $r->Remark,
                $r->NewPrice !== null && $r->CurrentPrice > 0 ? round(($r->NewPrice - $r->CurrentPrice) / $r->CurrentPrice * 100, 2) : null,
                $r->Status,
            ],
        );
    }

    /** @return array{0: string, 1: string, 2: ?int} period from, period to, supplier */
    private function filters(Request $request): array
    {
        return [
            (string) $request->input('dateFrom'),
            (string) $request->input('dateTo'),
            $request->filled('supplierId') ? (int) $request->input('supplierId') : null,
        ];
    }
}
