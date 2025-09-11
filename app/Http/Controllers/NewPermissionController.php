<?php

namespace App\Http\Controllers;

use App\Models\NewPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewPermissionController extends Controller
{
    public function index()
    {
        $permissions = NewPermission::all()->groupBy(['role', 'permission_key']);
        return view('pages.permissions', compact('permissions'));
    }

   public function store(Request $request)
{
    try {
        // Truncate before transaction
        NewPermission::truncate();

        DB::beginTransaction();

        $permissions = $request->input('permissions', []);

        foreach ($permissions as $role => $perms) {
            foreach ($perms as $key => $value) {
                NewPermission::create([
                    'role' => $role,
                    'permission_key' => $key,
                    'can_view' => in_array($role, ['viewer', 'member', 'manager', 'admin', 'superadmin']),
                    'can_create' => in_array($role, ['member', 'manager', 'admin', 'superadmin']),
                    'can_edit' => in_array($role, ['manager', 'admin', 'superadmin']),
                ]);
            }
        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Permissions saved successfully']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => 'Error saving permissions: ' . $e->getMessage()], 500);
    }
}


    public function checkPermission($role, $permissionKey)
    {
        $permission = NewPermission::where('role', $role)
            ->where('permission_key', $permissionKey)
            ->first();

        if (!$permission) {
            return false;
        }

        return [
            'can_view' => $permission->can_view,
            'can_create' => $permission->can_create,
            'can_edit' => $permission->can_edit,
            'can_delete' => $permission->can_delete,
        ];
    }
}
