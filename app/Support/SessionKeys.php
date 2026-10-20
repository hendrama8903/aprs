<?php

namespace App\Support;

/**
 * Nama key session, port dari Common/Constants/SessionKeys.cs.
 * Dikumpulkan di satu tempat supaya tidak ada typo antar controller/view.
 */
final class SessionKeys
{
    public const UserId = 'UserId';
    public const Username = 'Username';
    public const FullName = 'FullName';
    public const Email = 'Email';
    public const IsAdUser = 'IsAdUser';
    public const RoleId = 'RoleId';
    public const RoleCode = 'RoleCode';
    public const RoleName = 'RoleName';
    public const MenuHtml = 'MenuHtml';
    public const Menus = 'Menus';
    public const Permissions = 'Permissions';
}
