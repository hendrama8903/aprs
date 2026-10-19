<?php

namespace App\Http\Controllers;

use App\Repositories\AuthRepo;
use App\Support\PasswordHasher;
use App\Support\SessionAuth;
use App\Support\SessionKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Port dari Controllers/AuthController.cs. */
class AuthController extends Controller
{
    private const INVALID_LOGIN_MESSAGE = 'Invalid username or password.';

    public function __construct(private AuthRepo $authRepo)
    {
    }

    public function login(Request $request)
    {
        if (SessionAuth::isLoggedIn()) {
            return redirect('/');
        }

        return view('auth.login', [
            'returnUrl' => $request->query('returnUrl', ''),
        ]);
    }

    public function validateLogin(Request $request)
    {
        try {
            $username = trim((string) $request->input('username', ''));
            $password = (string) $request->input('password', '');

            if ($username === '' || $password === '') {
                return response()->json(['status' => false, 'message' => 'Username and password are required.']);
            }

            $user = $this->authRepo->getUserByUsername($username);
            if ($user === null || !$user->IsActive) {
                Log::warning("Login failed for {$username}: user not found or inactive");

                return response()->json(['status' => false, 'message' => self::INVALID_LOGIN_MESSAGE]);
            }

            // Active Directory belum didukung di fase ini - hanya akun lokal.
            if ($user->IsAdUser) {
                return response()->json(['status' => false, 'message' => 'Active Directory login is not available yet.']);
            }

            if (!PasswordHasher::verify($password, $user->PasswordHash)) {
                Log::warning("Login failed for {$username}: invalid password");

                return response()->json(['status' => false, 'message' => self::INVALID_LOGIN_MESSAGE]);
            }

            $menus = $this->authRepo->getMenuByRole((int) $user->RoleId);
            $permissions = $this->authRepo->getPermissionByRole((int) $user->RoleId);
            SessionAuth::signIn($user, $menus, $permissions);
            $this->authRepo->updateLastLogin((int) $user->UserId, $user->Username);

            $returnUrl = (string) $request->input('returnUrl', '');
            $redirectUrl = $returnUrl !== '' && str_starts_with($returnUrl, '/') ? $returnUrl : '/';

            return response()->json(['status' => true, 'message' => 'Login successful!', 'data' => $redirectUrl]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            if (session(SessionKeys::IsAdUser) === '1') {
                return response()->json(['status' => false, 'message' => 'Active Directory users must change their password through Windows.']);
            }

            $newPassword = (string) $request->input('newPassword', '');
            if (strlen($newPassword) < 8) {
                return response()->json(['status' => false, 'message' => 'New password must be at least 8 characters.']);
            }

            $user = $this->authRepo->getUserByUsername($this->loginUsername());
            if ($user === null || !PasswordHasher::verify((string) $request->input('currentPassword', ''), $user->PasswordHash)) {
                return response()->json(['status' => false, 'message' => 'Current password is incorrect.']);
            }

            $result = $this->authRepo->changePassword((int) $user->UserId, PasswordHasher::hash($newPassword), $this->loginUsername());

            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function logout()
    {
        session()->flush();

        return redirect()->route('auth.login');
    }
}
