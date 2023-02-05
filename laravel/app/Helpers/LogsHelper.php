<?php

namespace App\Helpers;

use Illuminate\Http\Request;

use App\Models\Logs\InternalLog;
use App\Models\Logs\InternalLogsType;

use App\Helpers\JwtHelper;


class LogsHelper
{
    public static function addLogEntry(Request $request, $type, $action, $newInfo = null, $oldInfo = null)
    {
        $log_type = InternalLogsType::where('type', $type)->first();
        $log = new InternalLog();
        $log->user_id = JwtHelper::getUser();
        $log->client_ip = $request->getClientIp();
        $log->action = $action;
        $log->item_id = $newInfo ? $newInfo->id : $oldInfo->id;
        $log->type_id = $log_type ? $log_type->id : null;
        $log->new_info = $newInfo;
        $log->old_info = $oldInfo;
        $log->save();
    }
}
