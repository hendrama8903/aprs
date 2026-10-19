<?php

namespace App\Http\Controllers;

use App\Repositories\AprRepo;

/**
 * Approval Inbox: dokumen APR yang menunggu approval user login, dan riwayat aksinya.
 * Approve/Reject dilakukan dari form APR (AprController).
 */
class ApprovalController extends Controller
{
    public function __construct(private AprRepo $aprRepo)
    {
    }

    public function index()
    {
        return view('approval.index');
    }

    public function search()
    {
        return $this->jsonData(fn () => $this->aprRepo->inbox((int) $this->loginUserId()));
    }

    public function history()
    {
        return $this->jsonData(fn () => $this->aprRepo->myHistory((int) $this->loginUserId()));
    }
}
