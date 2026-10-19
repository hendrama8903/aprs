<?php

namespace App\Repositories;

use App\Models\ApprovalStep;
use App\Models\Apr;
use App\Models\AprApproval;
use App\Models\AprDetail;
use App\Models\ItemPrice;
use App\Models\Notification;
use App\Models\Supplier;
use App\Models\UserAccount;
use App\Support\DBResult;
use App\Support\DocNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dokumen Approval for Price Confirmation.
 *
 * Alur status: DRAFT -> (submit) IN_APPROVAL -> (approve tiap tahap) APPROVED -> (issue surat) ISSUED.
 * Reject di tahap mana pun -> REJECTED; pembuat merevisi lalu submit ulang dan approval dimulai lagi dari tahap pertama.
 *
 * Harga current, qty dan statistik PO selalu dihitung ulang di server dari price master & transaksi PO
 * setiap kali draft disimpan; setelah submit nilainya menjadi snapshot yang dilihat approver.
 */
class AprRepo
{
    public function __construct(private ApprovalStepRepo $approvalStepRepo)
    {
    }

    // ---------- Query ----------

    /** @return object[] */
    public function search(?string $status, ?int $supplierId, ?string $dateFrom, ?string $dateTo): array
    {
        $steps = ApprovalStep::query()->with('approver:user_id,full_name')->get()->keyBy('step_no');

        return Apr::query()
            ->with(['supplier:supplier_id,supplier_code,supplier_name', 'preparedBy:user_id,full_name', 'details'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($dateFrom, fn ($q) => $q->whereDate('doc_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('doc_date', '<=', $dateTo))
            ->orderByDesc('apr_id')
            ->get()
            ->map(function (Apr $a) use ($steps) {
                $totals = $this->totals($a, $a->details->all());
                $step = $a->current_step_no ? $steps->get($a->current_step_no) : null;

                return (object) [
                    'AprId' => $a->apr_id,
                    'DocNo' => $a->doc_no,
                    'DocDate' => $a->doc_date,
                    'SupplierCode' => $a->supplier->supplier_code,
                    'SupplierName' => $a->supplier->supplier_name,
                    'PartCategory' => $a->part_category,
                    'EffectiveFrom' => $a->effective_from,
                    'EffectiveTo' => $a->effective_to,
                    'ItemCount' => $a->details->count(),
                    'TotalCurrent' => $totals->CurrentAmount,
                    'TotalNew' => $totals->NewAmount,
                    'TotalDiffPct' => $totals->DiffPct,
                    'Status' => $a->status,
                    'CurrentStep' => $step?->step_name,
                    'CurrentApprover' => $step?->approver?->full_name,
                    'PreparedBy' => $a->preparedBy->full_name,
                    'ExtDocNo' => $a->ext_doc_no,
                    'CreatedBy' => $a->created_by,
                    'CreatedDate' => $a->created_at,
                    'ModifiedBy' => $a->updated_by,
                    'ModifiedDate' => $a->updated_at,
                ];
            })
            ->all();
    }

    /** Dokumen yang menunggu approval user ini (berdasarkan approver tahap saat ini). @return object[] */
    public function inbox(int $userId): array
    {
        $stepNos = ApprovalStep::query()->where('approver_user_id', $userId)->where('is_active', true)->pluck('step_no');
        if ($stepNos->isEmpty()) {
            return [];
        }

        $ids = Apr::query()->where('status', Apr::StatusInApproval)->whereIn('current_step_no', $stepNos)->pluck('apr_id')->all();

        return array_values(array_filter(
            $this->search(Apr::StatusInApproval, null, null, null),
            fn ($a) => in_array($a->AprId, $ids, true),
        ));
    }

    /** Riwayat aksi approval user ini. @return object[] */
    public function myHistory(int $userId): array
    {
        return AprApproval::query()
            ->join('aprs', 'aprs.apr_id', '=', 'apr_approvals.apr_id')
            ->join('suppliers', 'suppliers.supplier_id', '=', 'aprs.supplier_id')
            ->where('apr_approvals.user_id', $userId)
            ->whereIn('apr_approvals.action', ['APPROVE', 'REJECT'])
            ->orderByDesc('apr_approvals.apr_approval_id')
            ->limit(200)
            ->get(['apr_approvals.*', 'aprs.doc_no', 'aprs.status', 'suppliers.supplier_name'])
            ->map(fn ($h) => (object) [
                'AprApprovalId' => $h->apr_approval_id,
                'AprId' => $h->apr_id,
                'DocNo' => $h->doc_no,
                'SupplierName' => $h->supplier_name,
                'StepName' => $h->step_name,
                'Action' => $h->action,
                'Note' => $h->note,
                'ActionDate' => $h->created_at,
                'Status' => $h->status,
            ])
            ->all();
    }

    public function getByKey(int $aprId, UserAccount $user, array $permissions): ?object
    {
        $apr = Apr::query()->with(['supplier', 'preparedBy', 'details', 'approvals'])->find($aprId);
        if ($apr === null) {
            return null;
        }

        $details = $apr->details->map(fn (AprDetail $d) => $this->mapDetail($apr, $d))->all();
        $currentStep = $apr->current_step_no
            ? ApprovalStep::query()->with('approver')->where('step_no', $apr->current_step_no)->first()
            : null;

        $editable = $apr->isEditable() && $permissions['edit'];

        return (object) [
            'Header' => $this->mapHeader($apr),
            'Details' => $details,
            'Totals' => $this->totals($apr, $apr->details->all()),
            'Approvals' => $apr->approvals->map(fn (AprApproval $h) => (object) [
                'Cycle' => $h->cycle,
                'StepName' => $h->step_name,
                'Action' => $h->action,
                'UserName' => $h->user_name,
                'Note' => $h->note,
                'ActionDate' => $h->created_at,
            ])->all(),
            'Signatures' => $this->signatures($apr),
            'CurrentStep' => $currentStep ? (object) [
                'StepNo' => $currentStep->step_no,
                'StepName' => $currentStep->step_name,
                'ApproverName' => $currentStep->approver?->full_name,
            ] : null,
            'Can' => (object) [
                'Edit' => $editable,
                'Submit' => $editable,
                'Delete' => $apr->status === Apr::StatusDraft && (int) $apr->cycle === 0 && $permissions['delete'],
                'Approve' => $apr->status === Apr::StatusInApproval && $currentStep !== null && (int) $currentStep->approver_user_id === (int) $user->user_id,
                'Issue' => $apr->status === Apr::StatusApproved && $permissions['issue'],
                'PrintLetter' => $apr->status === Apr::StatusIssued,
            ],
        ];
    }

    /**
     * Item yang bisa ditambahkan: harga aktif supplier di price master + qty & statistik PO pada periode pembelian.
     *
     * @return object[]
     */
    public function candidateItems(int $supplierId, string $purchaseFrom, string $purchaseTo): array
    {
        $prices = $this->activePrices($supplierId);
        $stats = $this->poStats($supplierId, array_keys($prices), $purchaseFrom, $purchaseTo);

        return array_values(array_map(fn (ItemPrice $p) => (object) [
            'ItemCode' => $p->item_code,
            'ItemName' => $p->item_name,
            'Unit' => $p->unit,
            'Currency' => $p->currency,
            'CurrentPrice' => $p->price,
            'Quotation' => $p->quotation,
            'Qty' => $stats[$p->item_code]->qty ?? 0,
            'PoCount' => $stats[$p->item_code]->po_count ?? 0,
            'PoAvgPrice' => $stats[$p->item_code]->avg_price ?? null,
            'PoMinPrice' => $stats[$p->item_code]->min_price ?? null,
            'PoMaxPrice' => $stats[$p->item_code]->max_price ?? null,
            'PoLastPrice' => $stats[$p->item_code]->last_price ?? null,
        ], $prices));
    }

    // ---------- Simpan draft ----------

    /**
     * @param array $data   field header (PascalCase)
     * @param array $lines  [{ItemCode, NewPrice, Remark}]
     */
    public function save(array $data, array $lines, UserAccount $user): DBResult
    {
        $aprId = (int) ($data['AprId'] ?? 0);
        $apr = $aprId ? Apr::query()->find($aprId) : null;
        if ($aprId && $apr === null) {
            return new DBResult(false, 'Document not found.');
        }
        if ($apr !== null && !$apr->isEditable()) {
            return new DBResult(false, "Document {$apr->doc_no} is {$apr->status} and can no longer be edited.");
        }

        $header = $this->validateHeader($data);
        if (is_string($header)) {
            return new DBResult(false, $header);
        }

        $prices = $this->activePrices($header['supplier_id']);
        $codes = [];
        foreach ($lines as $i => $line) {
            $code = trim((string) ($line['ItemCode'] ?? ''));
            $no = $i + 1;
            if (!isset($prices[$code])) {
                return new DBResult(false, "Line {$no}: item {$code} has no active price for this supplier in the price master.");
            }
            if (isset($codes[$code])) {
                return new DBResult(false, "Line {$no}: item {$code} is listed more than once.");
            }
            $newPrice = $line['NewPrice'] ?? null;
            if (!is_numeric($newPrice) || (float) $newPrice <= 0) {
                return new DBResult(false, "Line {$no}: new price for item {$code} must be greater than zero.");
            }
            $codes[$code] = true;
        }

        $stats = $this->poStats($header['supplier_id'], array_keys($codes), $header['purchase_from'], $header['purchase_to']);

        return DB::transaction(function () use ($apr, $header, $lines, $prices, $stats, $user) {
            if ($apr === null) {
                $apr = Apr::create($header + [
                    'doc_no' => DocNumber::next('internal', Carbon::parse($header['doc_date'])),
                    'status' => Apr::StatusDraft,
                    'prepared_by_user_id' => $user->user_id,
                    'created_by' => $user->username,
                ]);
            } else {
                $apr->update($header + ['updated_by' => $user->username]);
            }

            $apr->details()->delete();
            foreach (array_values($lines) as $i => $line) {
                $price = $prices[trim($line['ItemCode'])];
                $s = $stats[$price->item_code] ?? null;
                AprDetail::create([
                    'apr_id' => $apr->apr_id,
                    'line_no' => $i + 1,
                    'item_id' => $price->item_id,
                    'item_code' => $price->item_code,
                    'item_name' => $price->item_name,
                    'unit' => $price->unit,
                    'currency' => $price->currency,
                    'qty' => $s->qty ?? 0,
                    'current_price' => $price->price,
                    'new_price' => (float) $line['NewPrice'],
                    'remark' => $this->nullable($line['Remark'] ?? null, 255),
                    'po_count' => $s->po_count ?? 0,
                    'po_avg_price' => $s->avg_price ?? null,
                    'po_min_price' => $s->min_price ?? null,
                    'po_max_price' => $s->max_price ?? null,
                    'po_last_price' => $s->last_price ?? null,
                    'po_last_date' => $s->last_date ?? null,
                ]);
            }

            return new DBResult(true, "Document {$apr->doc_no} saved.", $apr->apr_id);
        });
    }

    public function delete(int $aprId): DBResult
    {
        $apr = Apr::query()->find($aprId);
        if ($apr === null) {
            return new DBResult(false, 'Document not found.');
        }
        if ($apr->status !== Apr::StatusDraft || $apr->cycle > 0) {
            return new DBResult(false, "Document {$apr->doc_no} has approval history and cannot be deleted.");
        }

        $apr->delete();

        return new DBResult(true, "Document {$apr->doc_no} deleted.");
    }

    // ---------- Workflow ----------

    public function submit(int $aprId, UserAccount $user): DBResult
    {
        return DB::transaction(function () use ($aprId, $user) {
            $apr = Apr::query()->with('details')->lockForUpdate()->find($aprId);
            if ($apr === null) {
                return new DBResult(false, 'Document not found.');
            }
            if (!$apr->isEditable()) {
                return new DBResult(false, "Document {$apr->doc_no} is already {$apr->status}.");
            }
            if ($apr->details->isEmpty()) {
                return new DBResult(false, 'Add at least one item before submitting.');
            }

            $threshold = (float) config('apr.remark_threshold_pct');
            $errors = [];
            foreach ($apr->details as $d) {
                $line = $this->mapDetail($apr, $d);
                if ($line->NeedRemark && trim((string) $d->remark) === '') {
                    $errors[] = "Line {$d->line_no} ({$d->item_code}): price increases {$line->MovementPct}% (above {$threshold}%), remark is required.";
                }
            }
            if ($this->usesCurrency($apr, 'USD') && ($apr->rate_usd_current <= 0 || $apr->rate_usd_new <= 0)) {
                $errors[] = 'USD exchange rate (current and new) is required because some items are in USD.';
            }
            if ($this->usesCurrency($apr, 'JPY') && ($apr->rate_jpy_current <= 0 || $apr->rate_jpy_new <= 0)) {
                $errors[] = 'JPY exchange rate (current and new) is required because some items are in JPY.';
            }

            $steps = $this->approvalStepRepo->activeSteps();
            if ($steps === []) {
                $errors[] = 'No active approval step. Please contact the administrator.';
            }
            foreach ($steps as $step) {
                if ($step->approver === null || !$step->approver->is_active) {
                    $errors[] = "Approval step {$step->step_name} has no active approver. Please contact the administrator.";
                }
            }
            if ($errors !== []) {
                return new DBResult(false, implode("\n", $errors));
            }

            $first = $steps[0];
            $apr->update([
                'status' => Apr::StatusInApproval,
                'current_step_no' => $first->step_no,
                'cycle' => $apr->cycle + 1,
                'submitted_at' => now(),
                'updated_by' => $user->username,
            ]);
            $this->log($apr, null, 'Prepared (Staff)', 'SUBMIT', $user, null);
            $this->notify($first->approver_user_id, 'APR waiting for your approval', "{$apr->doc_no} - {$apr->supplier->supplier_name} needs your approval as {$first->step_name}.", $apr);

            return new DBResult(true, "Document {$apr->doc_no} submitted to {$first->step_name} ({$first->approver->full_name}).");
        });
    }

    public function approve(int $aprId, UserAccount $user, ?string $note): DBResult
    {
        return DB::transaction(function () use ($aprId, $user, $note) {
            [$apr, $step, $error] = $this->lockForApproval($aprId, $user);
            if ($error !== null) {
                return new DBResult(false, $error);
            }

            $this->log($apr, $step->step_no, $step->step_name, 'APPROVE', $user, $note);

            $next = collect($this->approvalStepRepo->activeSteps())->first(fn (ApprovalStep $s) => $s->step_no > $step->step_no);
            if ($next !== null) {
                $apr->update(['current_step_no' => $next->step_no, 'updated_by' => $user->username]);
                $this->notify($next->approver_user_id, 'APR waiting for your approval', "{$apr->doc_no} - {$apr->supplier->supplier_name} needs your approval as {$next->step_name}.", $apr);

                return new DBResult(true, "Approved. Document {$apr->doc_no} moves to {$next->step_name}.");
            }

            $apr->update([
                'status' => Apr::StatusApproved,
                'current_step_no' => null,
                'approved_at' => now(),
                'updated_by' => $user->username,
            ]);
            $this->notify($apr->prepared_by_user_id, 'APR approved', "{$apr->doc_no} - {$apr->supplier->supplier_name} is fully approved. You can now issue the Price Confirmation letter.", $apr);

            return new DBResult(true, "Approved. Document {$apr->doc_no} is fully approved.");
        });
    }

    public function reject(int $aprId, UserAccount $user, ?string $note): DBResult
    {
        if (trim((string) $note) === '') {
            return new DBResult(false, 'Please write the reason for rejection.');
        }

        return DB::transaction(function () use ($aprId, $user, $note) {
            [$apr, $step, $error] = $this->lockForApproval($aprId, $user);
            if ($error !== null) {
                return new DBResult(false, $error);
            }

            $this->log($apr, $step->step_no, $step->step_name, 'REJECT', $user, $note);
            $apr->update([
                'status' => Apr::StatusRejected,
                'current_step_no' => null,
                'updated_by' => $user->username,
            ]);
            $this->notify($apr->prepared_by_user_id, 'APR rejected', "{$apr->doc_no} was rejected by {$user->full_name} ({$step->step_name}): " . mb_substr($note, 0, 300), $apr);

            return new DBResult(true, "Document {$apr->doc_no} rejected and returned to the preparer.");
        });
    }

    public function issue(int $aprId, UserAccount $user): DBResult
    {
        return DB::transaction(function () use ($aprId, $user) {
            $apr = Apr::query()->lockForUpdate()->find($aprId);
            if ($apr === null) {
                return new DBResult(false, 'Document not found.');
            }
            if ($apr->status !== Apr::StatusApproved) {
                return new DBResult(false, "Only approved documents can be issued. {$apr->doc_no} is {$apr->status}.");
            }

            $apr->update([
                'status' => Apr::StatusIssued,
                'ext_doc_no' => DocNumber::next('external', now()),
                'issued_at' => now(),
                'issued_by' => $user->username,
                'updated_by' => $user->username,
            ]);
            $this->log($apr, null, 'Issued', 'ISSUE', $user, "Letter {$apr->ext_doc_no}");

            return new DBResult(true, "Price Confirmation letter {$apr->ext_doc_no} issued.", $apr->apr_id);
        });
    }

    // ---------- Untuk cetak ----------

    /** Data lengkap untuk PDF (form APR internal & surat eksternal). */
    public function printData(int $aprId): ?object
    {
        $apr = Apr::query()->with(['supplier', 'preparedBy', 'details', 'approvals'])->find($aprId);
        if ($apr === null) {
            return null;
        }

        return (object) [
            'Apr' => $apr,
            'Details' => $apr->details->map(fn (AprDetail $d) => $this->mapDetail($apr, $d))->all(),
            'Totals' => $this->totals($apr, $apr->details->all()),
            'Signatures' => $this->signatures($apr),
        ];
    }

    // ---------- Helper ----------

    /** @return array<string,ItemPrice> item_code -> harga aktif terbaru supplier */
    private function activePrices(int $supplierId): array
    {
        $result = [];
        // Kalau ada harga aktif ganda (cacat data OGS), pakai yang paling baru di-update.
        $prices = ItemPrice::query()
            ->where('supplier_id', $supplierId)
            ->where('is_active', true)
            ->orderBy('item_code')
            ->orderByDesc('ogs_update_date')
            ->orderByDesc('item_price_id')
            ->get();
        foreach ($prices as $p) {
            $result[$p->item_code] ??= $p;
        }

        return $result;
    }

    /**
     * Statistik PO per item untuk supplier & periode: qty total, jumlah PO, harga rata-rata tertimbang qty,
     * min, max, dan harga PO terakhir.
     *
     * @return array<string,object>
     */
    private function poStats(int $supplierId, array $itemCodes, string $from, string $to): array
    {
        if ($itemCodes === []) {
            return [];
        }

        $stats = DB::table('po_transactions')
            ->where('supplier_id', $supplierId)
            ->whereIn('item_code', $itemCodes)
            ->whereBetween('po_date', [$from, $to])
            ->groupBy('item_code')
            ->get([
                'item_code',
                DB::raw('SUM(qty) AS qty'),
                DB::raw('COUNT(DISTINCT po_no) AS po_count'),
                DB::raw('SUM(amount) / NULLIF(SUM(qty), 0) AS avg_price'),
                DB::raw('MIN(unit_price) AS min_price'),
                DB::raw('MAX(unit_price) AS max_price'),
            ])
            ->keyBy('item_code')
            ->all();

        // Harga PO terakhir per item (tanggal terbaru, lalu nomor PO terbesar).
        $last = DB::table('po_transactions')
            ->where('supplier_id', $supplierId)
            ->whereIn('item_code', array_keys($stats))
            ->whereBetween('po_date', [$from, $to])
            ->orderBy('item_code')
            ->orderByDesc('po_date')
            ->orderByDesc('po_no')
            ->get(['item_code', 'unit_price', 'po_date']);
        foreach ($last as $row) {
            if (!isset($stats[$row->item_code]->last_price)) {
                $stats[$row->item_code]->last_price = (float) $row->unit_price;
                $stats[$row->item_code]->last_date = $row->po_date;
            }
        }

        foreach ($stats as $s) {
            $s->qty = (float) $s->qty;
            $s->po_count = (int) $s->po_count;
            $s->avg_price = $s->avg_price === null ? null : round((float) $s->avg_price, 4);
            $s->min_price = (float) $s->min_price;
            $s->max_price = (float) $s->max_price;
        }

        return $stats;
    }

    /** @return array|string data header siap simpan, atau pesan error. */
    private function validateHeader(array $data): array|string
    {
        $supplierId = (int) ($data['SupplierId'] ?? 0);
        if ($supplierId === 0 || !Supplier::query()->where('supplier_id', $supplierId)->exists()) {
            return 'Please choose a supplier.';
        }

        foreach (['PartCategory' => 'Part category', 'PartStatus' => 'Part status', 'Model' => 'Model', 'Background' => 'Background'] as $field => $label) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                return "{$label} is required.";
            }
        }

        $dates = [];
        foreach (['DocDate' => 'Document date', 'EffectiveFrom' => 'Effective from', 'EffectiveTo' => 'Effective to', 'PurchaseFrom' => 'Purchase period from', 'PurchaseTo' => 'Purchase period to'] as $field => $label) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                return "{$label} is required.";
            }
            try {
                $dates[$field] = Carbon::parse((string) $data[$field])->startOfDay();
            } catch (\Throwable) {
                return "{$label} is not a valid date.";
            }
        }
        if ($dates['EffectiveFrom']->gt($dates['EffectiveTo'])) {
            return 'Effective period: "from" must be before "to".';
        }
        if ($dates['PurchaseFrom']->gt($dates['PurchaseTo'])) {
            return 'Purchase period: "from" must be before "to".';
        }

        $rates = [];
        foreach (['RateUsdCurrent', 'RateUsdNew', 'RateJpyCurrent', 'RateJpyNew'] as $field) {
            $value = $data[$field] ?? 0;
            if ($value !== '' && $value !== null && (!is_numeric($value) || (float) $value < 0)) {
                return 'Exchange rates must be positive numbers.';
            }
            $rates[$field] = (float) ($value ?: 0);
        }

        $conclusion = ($data['Conclusion'] ?? 'ACCEPTABLE') === 'NOT_ACCEPTABLE' ? 'NOT_ACCEPTABLE' : 'ACCEPTABLE';

        return [
            'supplier_id' => $supplierId,
            'doc_date' => $dates['DocDate']->toDateString(),
            'part_category' => mb_substr(trim((string) $data['PartCategory']), 0, 50),
            'part_status' => mb_substr(trim((string) $data['PartStatus']), 0, 50),
            'model' => mb_substr(trim((string) $data['Model']), 0, 50),
            'background' => mb_substr(trim((string) $data['Background']), 0, 255),
            'effective_from' => $dates['EffectiveFrom']->toDateString(),
            'effective_to' => $dates['EffectiveTo']->toDateString(),
            'purchase_from' => $dates['PurchaseFrom']->toDateString(),
            'purchase_to' => $dates['PurchaseTo']->toDateString(),
            'rate_usd_current' => $rates['RateUsdCurrent'],
            'rate_usd_new' => $rates['RateUsdNew'],
            'rate_jpy_current' => $rates['RateJpyCurrent'],
            'rate_jpy_new' => $rates['RateJpyNew'],
            'conclusion' => $conclusion,
            'conclusion_note' => $this->nullable($data['ConclusionNote'] ?? null, 500),
            'letter_conditions' => $this->nullable($data['LetterConditions'] ?? null, 2000),
        ];
    }

    /** @return array{0: ?Apr, 1: ?ApprovalStep, 2: ?string} */
    private function lockForApproval(int $aprId, UserAccount $user): array
    {
        $apr = Apr::query()->with('supplier')->lockForUpdate()->find($aprId);
        if ($apr === null) {
            return [null, null, 'Document not found.'];
        }
        if ($apr->status !== Apr::StatusInApproval) {
            return [null, null, "Document {$apr->doc_no} is not waiting for approval (status {$apr->status})."];
        }

        $step = ApprovalStep::query()->where('step_no', $apr->current_step_no)->first();
        if ($step === null || (int) $step->approver_user_id !== (int) $user->user_id) {
            return [null, null, "You are not the approver for the current step of {$apr->doc_no}."];
        }

        return [$apr, $step, null];
    }

    private function usesCurrency(Apr $apr, string $currency): bool
    {
        return $apr->details->contains(fn (AprDetail $d) => $d->currency === $currency);
    }

    private function log(Apr $apr, ?int $stepNo, string $stepName, string $action, UserAccount $user, ?string $note): void
    {
        AprApproval::create([
            'apr_id' => $apr->apr_id,
            'cycle' => $apr->cycle,
            'step_no' => $stepNo,
            'step_name' => $stepName,
            'action' => $action,
            'user_id' => $user->user_id,
            'user_name' => $user->full_name,
            'note' => $this->nullable($note, 500),
        ]);
    }

    private function notify(?int $userId, string $title, string $message, Apr $apr): void
    {
        if ($userId === null) {
            return;
        }

        Notification::create([
            'user_id' => $userId,
            'title' => mb_substr($title, 0, 100),
            'message' => mb_substr($message, 0, 500),
            'url' => '/Apr/Form?id=' . $apr->apr_id,
        ]);
    }

    /**
     * Kolom tanda tangan form APR: Prepared (pembuat) + setiap tahap aktif, diisi dari aksi pada siklus terakhir.
     *
     * @return object[]
     */
    private function signatures(Apr $apr): array
    {
        $actions = $apr->approvals->where('cycle', $apr->cycle);
        $submit = $actions->firstWhere('action', 'SUBMIT');

        $result = [(object) [
            'Group' => 'PREPARED',
            'StepName' => 'Staff',
            'Name' => $apr->preparedBy?->full_name,
            'Date' => $submit?->created_at,
            'Status' => $submit ? 'SIGNED' : 'PENDING',
        ]];

        $steps = ApprovalStep::query()->with('approver')->where('is_active', true)->orderBy('step_no')->get();
        foreach ($steps as $step) {
            $done = $actions->where('step_no', $step->step_no)->whereIn('action', ['APPROVE', 'REJECT'])->last();
            $result[] = (object) [
                'Group' => $step->sign_group,
                'StepName' => $step->step_name,
                'Name' => $done?->user_name ?? $step->approver?->full_name,
                'Date' => $done?->created_at,
                'Status' => match (true) {
                    $done?->action === 'APPROVE' => 'SIGNED',
                    $done?->action === 'REJECT' => 'REJECTED',
                    $apr->status === Apr::StatusInApproval && (int) $apr->current_step_no === (int) $step->step_no => 'WAITING',
                    default => 'PENDING',
                },
            ];
        }

        return $result;
    }

    private function mapHeader(Apr $a): object
    {
        return (object) [
            'AprId' => $a->apr_id,
            'DocNo' => $a->doc_no,
            'DocDate' => $a->doc_date->toDateString(),
            'SupplierId' => $a->supplier_id,
            'SupplierCode' => $a->supplier->supplier_code,
            'SupplierName' => $a->supplier->supplier_name,
            'PartCategory' => $a->part_category,
            'PartStatus' => $a->part_status,
            'Model' => $a->model,
            'Background' => $a->background,
            'EffectiveFrom' => $a->effective_from->toDateString(),
            'EffectiveTo' => $a->effective_to->toDateString(),
            'PurchaseFrom' => $a->purchase_from->toDateString(),
            'PurchaseTo' => $a->purchase_to->toDateString(),
            'RateUsdCurrent' => $a->rate_usd_current,
            'RateUsdNew' => $a->rate_usd_new,
            'RateJpyCurrent' => $a->rate_jpy_current,
            'RateJpyNew' => $a->rate_jpy_new,
            'Conclusion' => $a->conclusion,
            'ConclusionNote' => $a->conclusion_note,
            'LetterConditions' => $a->letter_conditions,
            'Status' => $a->status,
            'Cycle' => $a->cycle,
            'PreparedBy' => $a->preparedBy->full_name,
            'SubmittedAt' => $a->submitted_at,
            'ApprovedAt' => $a->approved_at,
            'ExtDocNo' => $a->ext_doc_no,
            'IssuedAt' => $a->issued_at,
        ];
    }

    private function mapDetail(Apr $apr, AprDetail $d): object
    {
        $movement = round($d->new_price - $d->current_price, 4);
        $pct = $d->current_price > 0 ? round($movement / $d->current_price * 100, 2) : null;

        return (object) [
            'LineNo' => $d->line_no,
            'ItemCode' => $d->item_code,
            'ItemName' => $d->item_name,
            'Unit' => $d->unit,
            'Currency' => $d->currency,
            'Qty' => $d->qty,
            'CurrentPrice' => $d->current_price,
            'NewPrice' => $d->new_price,
            'Movement' => $movement,
            'MovementPct' => $pct,
            'Remark' => $d->remark,
            'PoCount' => $d->po_count,
            'PoAvgPrice' => $d->po_avg_price,
            'PoMinPrice' => $d->po_min_price,
            'PoMaxPrice' => $d->po_max_price,
            'PoLastPrice' => $d->po_last_price,
            'PoLastDate' => $d->po_last_date?->toDateString(),
            'CurrentAmount' => round($d->qty * $d->current_price * $apr->rate($d->currency, false), 2),
            'NewAmount' => round($d->qty * $d->new_price * $apr->rate($d->currency, true), 2),
            'NeedRemark' => $pct !== null && $pct > (float) config('apr.remark_threshold_pct'),
            'OverPoMax' => $d->po_max_price !== null && $d->new_price > $d->po_max_price,
        ];
    }

    /** Total purchase amount (IDR) current vs new berdasarkan qty periode pembelian. */
    private function totals(Apr $apr, array $details): object
    {
        $current = 0.0;
        $new = 0.0;
        foreach ($details as $d) {
            $current += $d->qty * $d->current_price * $apr->rate($d->currency, false);
            $new += $d->qty * $d->new_price * $apr->rate($d->currency, true);
        }

        return (object) [
            'CurrentAmount' => round($current, 2),
            'NewAmount' => round($new, 2),
            'Diff' => round($new - $current, 2),
            'DiffPct' => $current > 0 ? round(($new - $current) / $current * 100, 2) : null,
        ];
    }

    private function nullable(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }
}
