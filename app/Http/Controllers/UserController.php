<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepo;
use App\Support\PasswordHasher;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Port dari Controllers/UserController.cs. */
class UserController extends Controller
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(private UserRepo $userRepo)
    {
    }

    public function index()
    {
        return view('user.index', [
            ...$this->crudPermissions('USER'),
            'resetPassword' => $this->hasPermission(PermissionCodes::UserResetPassword),
        ]);
    }

    public function search(Request $request)
    {
        try {
            $data = $this->userRepo->search(
                $request->input('keyword'),
                $request->filled('roleId') ? (int) $request->input('roleId') : null,
                $request->input('isActive') === null || $request->input('isActive') === '' ? null : $request->boolean('isActive'),
            );

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function getByKey(Request $request)
    {
        try {
            $data = $this->userRepo->getByKey((int) $request->input('userId'));

            return $data === null
                ? response()->json(['status' => false, 'message' => 'User not found.'])
                : response()->json(['status' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function addEdit(Request $request)
    {
        try {
            $screenMode = (string) $request->input('screenMode');
            $isAdd = $screenMode === 'ADD';

            if (!$this->hasPermission($isAdd ? PermissionCodes::UserAdd : PermissionCodes::UserEdit)) {
                return $this->permissionDenied();
            }

            $isAdUser = $request->boolean('IsAdUser');
            $password = (string) $request->input('password', '');

            $passwordHash = null;
            if (!$isAdUser && $password !== '') {
                if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
                    return response()->json(['status' => false, 'message' => 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters.']);
                }
                $passwordHash = PasswordHasher::hash($password);
            } elseif (!$isAdUser && $isAdd) {
                return response()->json(['status' => false, 'message' => 'Password is required for local (non-AD) users.']);
            }

            $userId = $request->filled('UserId') ? (int) $request->input('UserId') : null;
            if (!$isAdd && $userId === $this->loginUserId() && !$request->boolean('IsActive')) {
                return response()->json(['status' => false, 'message' => 'You cannot deactivate your own account.']);
            }

            $data = [
                'UserId' => $userId,
                'Username' => $request->input('Username'),
                'FullName' => $request->input('FullName'),
                'Email' => $request->input('Email'),
                'Phone' => $request->input('Phone'),
                'EmployeeNo' => $request->input('EmployeeNo'),
                'RoleId' => (int) $request->input('RoleId'),
                'SupervisorUserId' => $request->filled('SupervisorUserId') ? (int) $request->input('SupervisorUserId') : null,
                'IsAdUser' => $isAdUser,
                'IsActive' => $request->boolean('IsActive'),
            ];

            $result = $isAdd
                ? $this->userRepo->insert($data, $passwordHash, $this->loginUsername())
                : $this->userRepo->update($data, $passwordHash, $this->loginUsername());

            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            if (!$this->hasPermission(PermissionCodes::UserResetPassword)) {
                return $this->permissionDenied();
            }

            $newPassword = (string) $request->input('newPassword', '');
            if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
                return response()->json(['status' => false, 'message' => 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters.']);
            }

            return response()->json($this->userRepo->resetPassword((int) $request->input('userId'), PasswordHasher::hash($newPassword), $this->loginUsername()));
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        if (in_array($this->loginUserId(), $ids, true)) {
            return response()->json(['status' => false, 'message' => 'You cannot delete your own account.']);
        }

        return $this->deleteMany(PermissionCodes::UserDelete, $ids, fn (int $id) => $this->userRepo->delete($id, $this->loginUsername()));
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'User',
            'USER',
            ['Username', 'Full Name', 'Employee No', 'Email', 'Phone', 'Role', 'Login Type', 'Status', 'Last Login'],
            $this->userRepo->search(null, null, null),
            fn (object $u) => [
                $u->Username, $u->FullName, $u->EmployeeNo, $u->Email, $u->Phone, $u->RoleName,
                $u->IsAdUser ? 'Active Directory' : 'Local', $u->IsActive ? 'Active' : 'Inactive', $u->LastLoginDate,
            ],
        );
    }
}
