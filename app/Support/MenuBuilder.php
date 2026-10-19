<?php

namespace App\Support;

/**
 * Render menu sidebar (markup sama dengan tema admin: .sidebar-menu / .treeview).
 * Port dari Helpers/MenuBuilder.cs.
 */
final class MenuBuilder
{
    /**
     * @param object[] $menus stdClass dengan properti MenuId, ParentMenuId, MenuName, MenuUrl, MenuIcon, SortOrder
     */
    public static function build(array $menus): string
    {
        $html = '';
        self::appendLevel($menus, null, $html);

        return $html;
    }

    private static function appendLevel(array $all, ?int $parentId, string &$html): void
    {
        $children = array_values(array_filter($all, static fn ($m) => ($m->ParentMenuId ?? null) === $parentId));

        usort($children, static function ($a, $b) {
            $sortCompare = ($a->SortOrder ?? 0) <=> ($b->SortOrder ?? 0);

            return $sortCompare !== 0 ? $sortCompare : strcmp($a->MenuName ?? '', $b->MenuName ?? '');
        });

        foreach ($children as $menu) {
            $hasChildren = self::hasAnyChild($all, $menu->MenuId);
            $name = htmlspecialchars($menu->MenuName ?? '', ENT_QUOTES, 'UTF-8');
            $icon = htmlspecialchars($menu->MenuIcon ?? 'fa-circle-o', ENT_QUOTES, 'UTF-8');

            if ($hasChildren) {
                $html .= '<li id="parentId' . $menu->MenuId . '" class="treeview"><a href="#"><i class="fa ' . $icon . '"></i> <span>' . $name . '</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a><ul class="treeview-menu">';
                self::appendLevel($all, $menu->MenuId, $html);
                $html .= '</ul></li>';
            } elseif (!empty($menu->MenuUrl) && $menu->MenuUrl !== '#') {
                $url = htmlspecialchars($menu->MenuUrl, ENT_QUOTES, 'UTF-8');
                $html .= '<li id="sidebarMenu' . $url . '" data-parentId="' . ($menu->ParentMenuId ?? '') . '"><a href="' . $url . '"><i class="fa ' . $icon . '"></i> <span>' . $name . '</span></a></li>';
            }
        }
    }

    private static function hasAnyChild(array $all, int $menuId): bool
    {
        foreach ($all as $m) {
            if (($m->ParentMenuId ?? null) === $menuId) {
                return true;
            }
        }

        return false;
    }
}
