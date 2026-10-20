<?php

namespace App\Support;

/**
 * Baca/tulis data login di session. Port dari Helpers/SessionHelper.cs.
 * Dipakai oleh controller, middleware, dan view.
 */
final class SessionAuth
{
    /**
     * @param object $user stdClass hasil usp_Auth_GetUserByUsername
     * @param object[] $menus stdClass[] hasil usp_Auth_GetMenuByRole
     * @param object[] $permissions stdClass[] hasil usp_Auth_GetPermissionByRole
     */
    public static function signIn(object $user, array $menus, array $permissions): void
    {
        session()->flush();

        session([
            SessionKeys::UserId => (int) $user->UserId,
            SessionKeys::Username => $user->Username,
            SessionKeys::FullName => $user->FullName,
            SessionKeys::Email => $user->Email ?? '',
            SessionKeys::IsAdUser => $user->IsAdUser ? '1' : '0',
            SessionKeys::RoleId => (int) $user->RoleId,
            SessionKeys::RoleCode => $user->RoleCode,
            SessionKeys::RoleName => $user->RoleName,
            SessionKeys::Menus => json_encode($menus),
            SessionKeys::MenuHtml => MenuBuilder::build($menus),
            SessionKeys::Permissions => implode(',', array_map(static fn ($p) => $p->PermissionCode, $permissions)),
        ]);
    }

    public static function isLoggedIn(): bool
    {
        return session()->has(SessionKeys::UserId);
    }

    public static function userId(): ?int
    {
        return session(SessionKeys::UserId);
    }

    public static function username(): string
    {
        return session(SessionKeys::Username, '');
    }

    /** @return object[] */
    public static function getMenus(): array
    {
        $json = session(SessionKeys::Menus);
        if (empty($json)) {
            return [];
        }

        return json_decode($json, false) ?: [];
    }

    public static function hasMenu(string $menuUrl): bool
    {
        foreach (self::getMenus() as $menu) {
            if (strcasecmp($menu->MenuUrl ?? '', $menuUrl) === 0) {
                return true;
            }
        }

        return false;
    }

    public static function hasPermission(string $permissionCode): bool
    {
        $permissions = explode(',', session(SessionKeys::Permissions, ''));

        foreach ($permissions as $code) {
            if (strcasecmp($code, $permissionCode) === 0) {
                return true;
            }
        }

        return false;
    }
}
