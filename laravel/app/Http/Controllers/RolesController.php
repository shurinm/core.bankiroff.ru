<?php

namespace App\Http\Controllers;

use App\Models\Roles\Role;
use App\Helpers\RolesHelper;
use App\Helpers\LogsHelper;

use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function getRoles(Request $request)
    {
        return Role::selectFields($request->isKeyValue)->get();
    }

    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Functions for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    public function addAllRelationships($role)
    {
        RolesHelper::addClusterToPermission($role->permissions);
    }

    public function getAll(Request $request, $xnumber = 10)
    {
        $roles = Role::orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        return $roles;
    }

    public function getByIdFull(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $this->addAllRelationships($role);
        return $role;
    }

    public function add(Request $request)
    {
        $role = new Role();
        $role->name = $request->name;
        $role->save();
        RolesHelper::fillRolesPermissionsInterceptions($request->clusters, 'permissions', $role->id);
        
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($role);
        LogsHelper::addLogEntry($request, "roles", "create", $role);
    }

    public function updateById(Request $request, $id)
    {
        $old_role = Role::findOrFail($id);
        $this->addAllRelationships($old_role);

        $role = Role::findOrFail($id);
        $role->name = $request->name;
        $role->save();
        RolesHelper::fillRolesPermissionsInterceptions($request->clusters, 'permissions', null, $role->id);
        
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($role);
        LogsHelper::addLogEntry($request, "roles", "update", $role, $old_role);
    }

    public function deleteById(Request $request, $id)
    {
        $old_role = Role::findOrFail($id);
        $this->addAllRelationships($old_role);

        $role = Role::findOrFail($id);
        $role->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "roles", "delete", null, $old_role);
    }
}
