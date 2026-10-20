<?php

namespace App\Http\Controllers;

use App\Repositories\ExchangeRateRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/**
 * Exchange rate master: add/edit/delete manual dan export Excel.
 */
class ExchangeRateController extends Controller
{
    public function __construct(private ExchangeRateRepo $exchangeRateRepo)
    {
    }

    public function index()
    {
        return view('exchange-rate.index', $this->crudPermissions('EXCHANGERATE') + [
            'currencies' => ExchangeRateRepo::Currencies,
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->exchangeRateRepo->search($request->input('currency') ?: null));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(
            fn () => $this->exchangeRateRepo->getByKey((int) $request->input('exchangeRateId')),
            'Exchange rate not found.',
        );
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['ExchangeRateId', 'Currency', 'Rate', 'ValidFrom', 'ValidTo', 'Remark']);

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'EXCHANGERATE',
            fn () => $this->exchangeRateRepo->insert($data, $this->loginUsername()),
            fn () => $this->exchangeRateRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::ExchangeRateDelete, $ids, fn (int $id) => $this->exchangeRateRepo->delete($id, $this->loginUsername()));
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'Exchange Rate',
            'EXCHANGE-RATE',
            ['Currency', 'Rate (IDR)', 'Valid From', 'Valid To', 'Status Today', 'Remark'],
            $this->exchangeRateRepo->search(null),
            fn (object $r) => [
                $r->Currency, $r->Rate, $r->ValidFrom?->format('d-m-Y'), $r->ValidTo?->format('d-m-Y'), $r->Status, $r->Remark,
            ],
        );
    }
}
