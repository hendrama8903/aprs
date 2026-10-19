<?php

namespace App\Http\Controllers;

use App\Repositories\ApprovalStepRepo;
use Illuminate\Http\Request;

/**
 * Pengaturan approver tetap per tahap approval APR.
 */
class ApprovalSettingController extends Controller
{
    public function __construct(private ApprovalStepRepo $approvalStepRepo)
    {
    }

    public function index()
    {
        return view('approval-setting.index', $this->crudPermissions('APPROVALSETTING'));
    }

    public function search()
    {
        return $this->jsonData(fn () => $this->approvalStepRepo->search());
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(
            fn () => $this->approvalStepRepo->getByKey((int) $request->input('stepId')),
            'Approval step not found.',
        );
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['StepId', 'ApproverUserId']);
        $data['IsActive'] = $request->boolean('IsActive');

        return $this->saveByMode(
            'EDIT',
            'APPROVALSETTING',
            fn () => null,
            fn () => $this->approvalStepRepo->update($data, $this->loginUsername()),
        );
    }
}
