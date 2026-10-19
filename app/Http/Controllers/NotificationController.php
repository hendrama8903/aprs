<?php

namespace App\Http\Controllers;

use App\Repositories\NotificationRepo;
use Illuminate\Http\Request;

/** Port dari Controllers/NotificationController.cs — notifikasi lonceng di topbar (cukup login). */
class NotificationController extends Controller
{
    public function __construct(private NotificationRepo $notificationRepo)
    {
    }

    public function list()
    {
        return $this->jsonData(fn () => $this->notificationRepo->search($this->loginUserId() ?? 0));
    }

    public function markRead(Request $request)
    {
        $notificationId = $request->filled('notificationId') ? (int) $request->input('notificationId') : null;

        return $this->runJson(fn () => $this->notificationRepo->markRead($this->loginUserId() ?? 0, $notificationId, $this->loginUsername()));
    }
}
