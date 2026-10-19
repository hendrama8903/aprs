<?php

namespace App\Repositories;

use App\Models\ApprovalStep;
use App\Models\UserAccount;
use App\Support\DBResult;

/**
 * Urutan approval APR. Tahap & urutannya tetap (dibuat seeder), yang bisa diubah: approver dan status aktif.
 */
class ApprovalStepRepo
{
    /** @return object[] */
    public function search(): array
    {
        return ApprovalStep::query()
            ->with('approver:user_id,username,full_name')
            ->orderBy('step_no')
            ->get()
            ->map(fn (ApprovalStep $s) => $this->mapStep($s))
            ->all();
    }

    public function getByKey(int $stepId): ?object
    {
        $step = ApprovalStep::query()->with('approver:user_id,username,full_name')->find($stepId);

        return $step === null ? null : $this->mapStep($step);
    }

    /** Tahap aktif berurutan, dipakai saat submit/approve. @return ApprovalStep[] */
    public function activeSteps(): array
    {
        return ApprovalStep::query()->with('approver')->where('is_active', true)->orderBy('step_no')->get()->all();
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $step = ApprovalStep::query()->find($data['StepId'] ?? 0);
        if ($step === null) {
            return new DBResult(false, 'Approval step not found.');
        }

        $isActive = (bool) ($data['IsActive'] ?? true);
        $approverId = (int) ($data['ApproverUserId'] ?? 0) ?: null;

        if ($isActive && $approverId === null) {
            return new DBResult(false, 'An active step must have an approver.');
        }
        if ($approverId !== null && !UserAccount::query()->where('user_id', $approverId)->where('is_active', true)->exists()) {
            return new DBResult(false, 'Approver must be an active user.');
        }
        if (!$isActive && ApprovalStep::query()->where('is_active', true)->where('step_id', '!=', $step->step_id)->doesntExist()) {
            return new DBResult(false, 'At least one approval step must stay active.');
        }

        // Dokumen yang sedang berjalan ikut memakai approver baru pada tahap ini (dibaca saat approve).
        $step->update([
            'approver_user_id' => $approverId,
            'is_active' => $isActive,
            'updated_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data updated successfully.');
    }

    private function mapStep(ApprovalStep $s): object
    {
        return (object) [
            'StepId' => $s->step_id,
            'StepNo' => $s->step_no,
            'StepCode' => $s->step_code,
            'StepName' => $s->step_name,
            'SignGroup' => $s->sign_group,
            'ApproverUserId' => $s->approver_user_id,
            'ApproverName' => $s->approver?->full_name,
            'ApproverUsername' => $s->approver?->username,
            'IsActive' => $s->is_active,
            'CreatedBy' => $s->created_by,
            'CreatedDate' => $s->created_at,
            'ModifiedBy' => $s->updated_by,
            'ModifiedDate' => $s->updated_at,
        ];
    }
}
