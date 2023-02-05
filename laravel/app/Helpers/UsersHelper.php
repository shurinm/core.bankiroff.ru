<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\User;

use App\Models\Roles\Role;
use App\Interceptions\Users\UsersNewsTagsInterception;


use Illuminate\Support\Facades\Log;

class UsersHelper
{
    public static function userAlreadyExists($id, $email, $nickname)
    {

        if (!$id) return !!User::where('email', $email)->orWhere('nickname', $nickname)->first();
        return !!User::where('id', '!=', $id)->where(function ($q) use ($email, $nickname) {
            $q->where('email', $email)->orWhere('nickname', $nickname);
        })->first();
    }


    public static function phoneAlreadyExists($phone)
    {
        return !!User::where('phone', $phone)->first();
    }
    public static function nicknameAlreadyExists($nickname)
    {
        return !!User::where('nickname', $nickname)->first();
    }
    public static function emailAlreadyExists($email)
    {
        return !!User::where('email', $email)->first();
    }

    public static function addRoleMeaning($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $role_obj = Role::where('id', $item->role_id)->select('name')->first();
                $item->role_title = $role_obj ? $role_obj->name : 'Не определено';
            }
        }

        return $items;
    }

    public static function addRoleMeaningToOneElement($item)
    {
        $role_obj = Role::where('id', $item->role_id)->select('name')->first();
        $item->role_title = $role_obj ? $role_obj->name : 'Не определено';
        return $item;
    }


    public static function fillInterceptions($items, $type = null, $new_id = null, $old_id = null)
    {
        if ($items == "DELETE") UsersHelper::deleteInterceptions($type, $old_id);
        $items = json_decode($items, true);
        if (!$items) return false;
        if ($items && count($items) > 0) {
            if ($old_id) {
                UsersHelper::deleteInterceptions($type, $old_id);
                $new_id = $old_id;
            }
            foreach ($items as $key => $item) {
                switch ($type) {
                    case 'news_tags':
                        $model =  new UsersNewsTagsInterception();
                        $model->user_id = $item['user_id'];
                        $model->news_tag_id =  $item['news_tag_id'];
                        $model->save();
                        break;
                }
            }
        }
        return $items;
    }

    public static function deleteInterceptions($type = null, $id = null)
    {
        switch ($type) {
            case 'news_tags':
                UsersNewsTagsInterception::where('user_id', $id)->delete();
                break;
        }
    }
}
