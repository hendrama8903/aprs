<?php

namespace App\Http\Controllers;

use App\Repositories\DocCounterRepo;
use Illuminate\Http\Request;

/**
 * Document Numbering: atur nomor urut berikutnya per jenis dokumen & tahun.
 */
class DocNumberingController extends Controller
{
    public function __construct(private DocCounterRepo $docCounterRepo)
    {
    }

    public function index()
    {
        return view('doc-numbering.index', $this->crudPermissions('DOCNUMBERING'));
    }

    public function search()
    {
        return $this->jsonData(fn () => $this->docCounterRepo->search());
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(
            fn () => $this->docCounterRepo->getByKey((string) $request->input('counterKey')),
            'Document numbering not found.',
        );
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['CounterKey', 'NextNo']);

        return $this->saveByMode(
            'EDIT',
            'DOCNUMBERING',
            fn () => null,
            fn () => $this->docCounterRepo->update($data, $this->loginUsername()),
        );
    }
}
