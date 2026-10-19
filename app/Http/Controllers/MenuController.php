<?php

namespace App\Http\Controllers;

use App\Repositories\MenuRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Port dari Controllers/MenuController.cs — Master Menu: MENU (grup sidebar) > SCREEN (halaman ber-URL) > BUTTON (permission aksi). */
class MenuController extends Controller
{
    public function __construct(private MenuRepo $menuRepo)
    {
    }

    public function index()
    {
        return view('menu.index', $this->crudPermissions('MENU'));
    }

    public function search()
    {
        try {
            return response()->json(['status' => true, 'data' => $this->menuRepo->search()]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function addEdit(Request $request)
    {
        $data = [
            'NodeType' => $request->input('NodeType'),
            'RecordId' => $request->filled('RecordId') ? (int) $request->input('RecordId') : null,
            'ParentMenuId' => $request->filled('ParentMenuId') ? (int) $request->input('ParentMenuId') : null,
            'NodeName' => $request->input('NodeName'),
            'MenuUrl' => $request->input('MenuUrl'),
            'MenuIcon' => $request->input('MenuIcon'),
            'PermissionCode' => $request->input('PermissionCode'),
            'SortOrder' => (int) $request->input('SortOrder', 0),
            'IsActive' => $request->boolean('IsActive'),
        ];

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'MENU',
            fn () => $this->menuRepo->insert($data, $this->loginUsername()),
            fn () => $this->menuRepo->update($data, $this->loginUsername()),
        );
    }

    public function delete(Request $request)
    {
        return $this->deleteMany(
            PermissionCodes::MenuDelete,
            [(int) $request->input('recordId')],
            fn (int $id) => $this->menuRepo->delete((string) $request->input('nodeType'), $id, $this->loginUsername()),
        );
    }

    public function downloadExcel()
    {
        $all = $this->menuRepo->search();

        // Urutkan sesuai pohon: induk lalu anak-anaknya.
        $ordered = [];
        $addLevel = function (?string $parentNodeId) use (&$addLevel, &$ordered, $all) {
            $children = array_filter($all, static fn ($n) => ($n->ParentNodeId ?? null) === $parentNodeId);
            usort($children, static fn ($a, $b) => ($a->SortOrder <=> $b->SortOrder) ?: strcmp($a->NodeName, $b->NodeName));

            foreach ($children as $node) {
                $ordered[] = $node;
                $addLevel($node->NodeId);
            }
        };
        $addLevel(null);

        return $this->exportExcel(
            'Menu Master',
            'MENU',
            ['Type', 'Parent', 'Name', 'URL', 'Button Code', 'Icon', 'Sort Order', 'Used By Roles', 'Status', 'Created By', 'Created Date', 'Modified By', 'Modified Date'],
            $ordered,
            fn (object $n) => [
                $n->NodeType, $n->ParentName, $n->NodeName, $n->MenuUrl, $n->PermissionCode, $n->MenuIcon, $n->SortOrder,
                $n->RoleCount, $n->IsActive ? 'Active' : 'Inactive', $n->CreatedBy, $n->CreatedDate, $n->ModifiedBy, $n->ModifiedDate,
            ],
        );
    }
}
