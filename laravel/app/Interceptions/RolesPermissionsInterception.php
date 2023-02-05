<?php

namespace App\Interceptions;

use Illuminate\Database\Eloquent\Model;

class RolesPermissionsInterception extends Model
{
    public function permission()
    {
        return $this->belongsTo('App\Models\Roles\Permission');
    }
}
