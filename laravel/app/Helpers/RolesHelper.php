<?php

namespace App\Helpers;

use App\Models\Roles\Role;
use App\Models\Roles\Permission;


use App\Interceptions\RolesPermissionsInterception;

class RolesHelper
{

    public static function fillRolesPermissionsInterceptions($items, $type = null, $new_product_id = null, $old_product_id = null)
    {
        if ($items == "DELETE") RolesHelper::deleteRolesPermissionsInterceptions($type, $old_product_id);
        $items = json_decode($items, true);
        if (!$items) return RolesHelper::deleteRolesPermissionsInterceptions($type, $old_product_id);;
        if ($items && count($items) > 0) {
            if ($old_product_id) {
                RolesHelper::deleteRolesPermissionsInterceptions($type, $old_product_id);
                $new_product_id = $old_product_id;
            }
            $default_permissions = ['add', 'update', 'read', 'delete', 'browse'];

            foreach ($items as $key => $item) {
                switch ($type) {
                        /* Permissions */
                    case 'permissions':
                        foreach ($default_permissions as $key => $permission) {
                            $access = $item . "." . $permission;
                            $permission_obj = Permission::where('access', $access)->first();
                            $model =  new RolesPermissionsInterception();
                            $model->role_id = $new_product_id;
                            $model->permission_id = $permission_obj->id;
                            $model->save();
                        }
                        break;
                }
            }
        }
        return $items;
    }


    public static function deleteRolesPermissionsInterceptions($type = null, $id = null)
    {

        switch ($type) {
                /* permissions */
            case 'permissions':
                RolesPermissionsInterception::where('role_id', $id)->delete();
                break;
        }
    }


    public static function addClusterToPermission($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $permission_obj = Permission::where('id', $item["value"])->first();
                $item['cluster'] = $permission_obj  ? $permission_obj->cluster : null;
            }
        }
        return $items;
    }
}
