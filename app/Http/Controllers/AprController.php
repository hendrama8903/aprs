<?php

namespace App\Http\Controllers;

use App\Models\UserAccount;
use App\Repositories\AprAttachmentRepo;
use App\Repositories\AprRepo;
use App\Repositories\ExchangeRateRepo;
use App\Support\DBResult;
use App\Support\PermissionCodes;
use App\Support\SessionAuth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Dokumen Approval for Price Confirmation: daftar, form header-detail, workflow, dan cetak PDF.
 * Form, GetByKey dan cetak juga dibuka approver dari Approval Inbox, jadi aksesnya cukup salah satu
 * dari menu APR List atau Approval Inbox (dicek di canView()).
 */
class AprController extends Controller
{
    public function __construct(private AprRepo $aprRepo, private AprAttachmentRepo $attachmentRepo)
    {
    }

    public function index()
    {
        return view('apr.index', [
            'add' => $this->hasPermission(PermissionCodes::AprAdd),
            'delete' => $this->hasPermission(PermissionCodes::AprDelete),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->aprRepo->search(
            $request->input('status'),
            $request->filled('supplierId') ? (int) $request->input('supplierId') : null,
            $request->input('dateFrom'),
            $request->input('dateTo'),
        ));
    }

    public function form(Request $request)
    {
        if (!$this->canView()) {
            return response()->view('errors.403', [], 403);
        }

        $aprId = (int) $request->query('id', 0);
        if ($aprId === 0 && !$this->hasPermission(PermissionCodes::AprAdd)) {
            return response()->view('errors.403', [], 403);
        }

        return view('apr.form', [
            'title' => $aprId === 0 ? 'New APR' : 'APR Document',
            'parentTitle' => 'Price Confirmation',
            'aprId' => $aprId,
            'defaults' => config('apr.defaults'),
            'headerOptions' => $this->aprRepo->headerOptions(),
            'attachment' => [
                'maxBytes' => AprAttachmentRepo::maxBytes(),
                'extensions' => AprAttachmentRepo::Extensions,
                'maxFiles' => AprAttachmentRepo::MaxFiles,
            ],
            'threshold' => config('apr.remark_threshold_pct'),
            'backUrl' => SessionAuth::hasMenu('/Apr/Index') ? url('/Apr/Index') : url('/Approval/Index'),
        ]);
    }

    public function getByKey(Request $request)
    {
        if (!$this->canView()) {
            return $this->permissionDenied();
        }

        return $this->jsonData(
            fn () => $this->aprRepo->getByKey((int) $request->input('aprId'), $this->user(), $this->permissions()),
            'Document not found.',
        );
    }

    public function candidateItems(Request $request)
    {
        return $this->jsonData(fn () => $this->aprRepo->candidateItems(
            (int) $request->input('supplierId'),
            (string) ($request->input('docDate') ?: now()->toDateString()),
            (string) $request->input('purchaseFrom'),
            (string) $request->input('purchaseTo'),
            \DateTime::createFromFormat('!Y-m-d', (string) $request->input('effectiveFrom')) ? (string) $request->input('effectiveFrom') : null,
        ));
    }

    /** Kurs dari master Exchange Rate: current pada tanggal dokumen, new pada awal effective period. */
    public function exchangeRates(Request $request, ExchangeRateRepo $exchangeRateRepo)
    {
        $date = fn (string $key) => \DateTime::createFromFormat('!Y-m-d', (string) $request->input($key)) ? (string) $request->input($key) : null;

        return $this->jsonData(fn () => [
            'Current' => ($d = $date('docDate')) ? $exchangeRateRepo->ratesOn($d) : null,
            'New' => ($d = $date('effectiveFrom')) ? $exchangeRateRepo->ratesOn($d) : null,
            'Options' => $exchangeRateRepo->options(),
        ]);
    }

    public function save(Request $request)
    {
        return $this->runJson(function () use ($request) {
            $header = (array) json_decode((string) $request->input('header'), true);
            $lines = (array) json_decode((string) $request->input('lines'), true);

            $isNew = (int) ($header['AprId'] ?? 0) === 0;
            if (!$this->hasPermission($isNew ? PermissionCodes::AprAdd : PermissionCodes::AprEdit)) {
                return $this->denied();
            }

            return $this->aprRepo->save($header, $lines, $this->user());
        });
    }

    public function delete(Request $request)
    {
        return $this->runJson(fn () => $this->hasPermission(PermissionCodes::AprDelete)
            ? $this->aprRepo->delete((int) $request->input('aprId'))
            : $this->denied());
    }

    public function submit(Request $request)
    {
        return $this->runJson(fn () => $this->hasPermission(PermissionCodes::AprEdit)
            ? $this->aprRepo->submit((int) $request->input('aprId'), $this->user())
            : $this->denied());
    }

    public function approve(Request $request)
    {
        // Hak approve ditentukan oleh Approval Setting (approver tahap saat ini), dicek di repository.
        return $this->runJson(fn () => $this->aprRepo->approve((int) $request->input('aprId'), $this->user(), $request->input('note')));
    }

    public function reject(Request $request)
    {
        return $this->runJson(fn () => $this->aprRepo->reject((int) $request->input('aprId'), $this->user(), $request->input('note')));
    }

    public function issue(Request $request)
    {
        return $this->runJson(fn () => $this->hasPermission(PermissionCodes::AprIssue)
            ? $this->aprRepo->issue((int) $request->input('aprId'), $this->user())
            : $this->denied());
    }

    // ---------- Lampiran (dokumen pendukung) ----------

    public function uploadAttachment(Request $request)
    {
        return $this->runJson(fn () => $this->canChangeAttachments()
            ? $this->attachmentRepo->upload((int) $request->input('aprId'), $request->file('file'), $this->loginUsername())
            : $this->denied());
    }

    public function deleteAttachment(Request $request)
    {
        return $this->runJson(fn () => $this->canChangeAttachments()
            ? $this->attachmentRepo->delete((int) $request->input('attachmentId'))
            : $this->denied());
    }

    public function downloadAttachment(Request $request)
    {
        if (!$this->canView()) {
            return response()->view('errors.403', [], 403);
        }
        $attachment = $this->attachmentRepo->find((int) $request->query('id'));
        abort_if($attachment === null, 404, 'Attachment not found.');

        return Storage::disk(AprAttachmentRepo::Disk)->download($attachment->stored_path, $attachment->file_name);
    }

    /** Lampiran boleh diubah oleh pembuat (APR-ADD, termasuk tepat setelah simpan pertama) atau editor (APR-EDIT). */
    private function canChangeAttachments(): bool
    {
        return $this->hasPermission(PermissionCodes::AprEdit) || $this->hasPermission(PermissionCodes::AprAdd);
    }

    /** Form APR internal (untuk arsip / tanda tangan basah bila diperlukan). */
    public function printApr(Request $request)
    {
        $data = $this->canView() ? $this->aprRepo->printData((int) $request->query('id')) : null;
        if ($data === null) {
            abort(404);
        }

        return $this->pdf('apr.pdf-apr', $data)->stream($this->fileName('APR', $data->Apr->doc_no));
    }

    /** Surat Price Confirmation ke supplier + lampiran daftar harga. Hanya untuk dokumen yang sudah ISSUED. */
    public function printLetter(Request $request)
    {
        $data = $this->canView() ? $this->aprRepo->printData((int) $request->query('id')) : null;
        if ($data === null || $data->Apr->ext_doc_no === null) {
            abort(404);
        }

        return $this->pdf('apr.pdf-letter', $data)->stream($this->fileName('PC', $data->Apr->ext_doc_no));
    }

    private function pdf(string $view, object $data): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView($view, ['d' => $data, 'company' => config('apr.company')])
            ->setPaper('a4', 'portrait')
            // Hanya karakter yang dipakai yang di-embed, supaya file tidak ~900 KB per dokumen.
            ->setOption('isFontSubsettingEnabled', true);
    }

    private function canView(): bool
    {
        return SessionAuth::hasMenu('/Apr/Index') || SessionAuth::hasMenu('/Approval/Index');
    }

    private function permissions(): array
    {
        return [
            'edit' => $this->hasPermission(PermissionCodes::AprEdit),
            'delete' => $this->hasPermission(PermissionCodes::AprDelete),
            'issue' => $this->hasPermission(PermissionCodes::AprIssue),
        ];
    }

    private function user(): UserAccount
    {
        return UserAccount::query()->findOrFail($this->loginUserId());
    }

    private function denied(): DBResult
    {
        return new DBResult(false, 'You do not have permission to perform this action.');
    }

    private function fileName(string $prefix, string $docNo): string
    {
        return $prefix . '_' . preg_replace('/[^A-Za-z0-9\-]+/', '-', $docNo) . '.pdf';
    }
}
