<?php

namespace App\Http\Controllers;

use App\Models\Apr;
use App\Repositories\AprRepo;
use App\Repositories\OgsImportRepo;
use App\Support\SessionAuth;

/** Dashboard APR: ringkasan status dokumen, inbox approval, dan kesegaran data OGS. */
class HomeController extends Controller
{
    public function __construct(private AprRepo $aprRepo, private OgsImportRepo $ogsImportRepo)
    {
    }

    public function index()
    {
        $canSeeApr = SessionAuth::hasMenu('/Apr/Index');
        $statusCounts = $canSeeApr
            ? Apr::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->all()
            : [];

        return view('home.index', [
            'canSeeApr' => $canSeeApr,
            'statusCounts' => $statusCounts,
            'inbox' => SessionAuth::hasMenu('/Approval/Index') ? $this->aprRepo->inbox((int) $this->loginUserId()) : null,
            'recent' => $canSeeApr ? array_slice($this->aprRepo->search(null, null, null, null), 0, 8) : [],
            'lastImports' => SessionAuth::hasMenu('/OgsImport/Index') ? $this->ogsImportRepo->lastImports() : null,
        ]);
    }
}
