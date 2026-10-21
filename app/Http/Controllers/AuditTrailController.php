<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Repositories\AuditTrailRepo;
use Illuminate\Http\Request;

/**
 * Audit Trail: lihat & export riwayat perubahan data dan aktivitas login (read-only).
 */
class AuditTrailController extends Controller
{
    public function __construct(private AuditTrailRepo $auditTrailRepo)
    {
    }

    public function index()
    {
        return view('audit-trail.index', [
            'modules' => $this->auditTrailRepo->modules(),
            'actions' => [
                AuditLog::ActionCreate, AuditLog::ActionUpdate, AuditLog::ActionDelete, AuditLog::ActionUpload,
                AuditLog::ActionLogin, AuditLog::ActionLoginFailed, AuditLog::ActionLogout,
            ],
        ]);
    }

    public function search(Request $request)
    {
        [$from, $to, $module, $action] = $this->filters($request);
        if ($error = $this->auditTrailRepo->validateRange($from, $to)) {
            return response()->json(['status' => false, 'message' => $error]);
        }

        return $this->jsonData(fn () => $this->auditTrailRepo->search($from, $to, $module, $action));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(
            fn () => $this->auditTrailRepo->getByKey((int) $request->input('auditId')),
            'Audit record not found.',
        );
    }

    public function downloadExcel(Request $request)
    {
        [$from, $to, $module, $action] = $this->filters($request);
        abort_if($this->auditTrailRepo->validateRange($from, $to) !== null, 422);

        return $this->exportExcel(
            'Audit Trail',
            'AUDIT-TRAIL',
            ['Date', 'User', 'Action', 'Module', 'Record', 'Description', 'Changes', 'IP Address'],
            $this->auditTrailRepo->export($from, $to, $module, $action),
            fn (object $r) => [
                $r->LogDate, $r->Username, $r->Action, $r->Module, $r->RecordLabel, $r->Description, $r->ChangesText, $r->IpAddress,
            ],
        );
    }

    /** @return array{0: string, 1: string, 2: ?string, 3: ?string} date from, date to, module, action */
    private function filters(Request $request): array
    {
        return [
            (string) $request->input('dateFrom'),
            (string) $request->input('dateTo'),
            $request->input('module') ?: null,
            $request->input('action') ?: null,
        ];
    }
}
