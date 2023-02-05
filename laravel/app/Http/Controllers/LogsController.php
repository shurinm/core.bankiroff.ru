<?php

namespace App\Http\Controllers;

use App\Models\Logs\InternalLog;

use Illuminate\Http\Request;
use File;
use Response;
class LogsController extends Controller
{
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Routes for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    public function addAllRelationships($log)
    {
        $log->user;
        $log->type;
    }

    /*For External logs*/
    public function getAllExternalLogs(Request $request)
    {
        $file = env('APP_ENV') == 'production' ? File::get('/var/www/www-root/data/logs/bankiroff.ru.access.log') : File::get(public_path() . '/robots.txt');
        $response = Response::make($file, 200);
        $response->header('Content-Type', 'application/txt');
        return $response;
    }


    /*For Internal logs*/
    public function getAllInternalLogs(Request $request, $xnumber = 10)
    {
        $logs = InternalLog::matchTypesIds($request->types ?? null)
            ->matchUsersIds($request->users ?? null)
            ->matchNickOrEmail($request->nickOrEmail ?? null)
            ->matchItemId($request->itemId ?? null)
            ->matchActions($request->actions ?? null)
            ->orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        foreach ($logs as $key => $log) {
            $this->addAllRelationships($log);
        }
        return $logs;
    }

    
    public function getInternalLogById(Request $request, $id)
    {
        $log = InternalLog::findOrFail($id);
        $this->addAllRelationships($log);
        return $log;
    }
}
