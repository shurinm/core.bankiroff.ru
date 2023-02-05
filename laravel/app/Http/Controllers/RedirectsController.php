<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Redirects\Redirect;

class RedirectsController extends Controller
{
    public function getRedirectByUrl1(Request $request)
    {
        return Redirect::active()->matchUrl($request->url)->first();
    }

    public function getRedirectByUrl(Request $request)
    {
        // return Redirect::active()->matchUrl($request->url)->first();

        $subdomain = $this->getSubdomainFromUrl($request->url);

        if ($subdomain != null) {
            $old_url = $this->removeSubdomainToUrl($request->url);
            $data = Redirect::active()->matchUrl($old_url)->first();
            if ($data==null) return null;
            $new_url = $this->addSubdomainToUrl($data->new_url, $subdomain);
            $data->old_url = $request->url;
            $data->new_url = $new_url;
            return json_encode($data);
        } else {
            return Redirect::active()->matchUrl($request->url)->first();
        }

    }

    public function getSubdomainFromUrl($url) {
        $url = str_replace('https://', '', $url);
        $url = str_replace('http://', '', $url);
        $dots = explode('.', $url);
        if ($dots[0]=='bankiroff') return null;
        return $dots[0];
    }

    public function addSubdomainToUrl($url, $subdomain) {
        return str_replace('bankiroff.ru', $subdomain.'.bankiroff.ru', $url);
    }

    public function removeSubdomainToUrl($url) {
        $subdomain = $this->getSubdomainFromUrl($url);
        if ($subdomain != null) {
            $url = str_replace($subdomain.'.', '', $url);
        }
        return $url;
    }







    public function getAll(Request $request, $xnumber = 10)
    {
        $redirects = Redirect::matchURLsLike($request->old_url ?? null, $request->new_url ?? null)
            ->matchCode($request->code ?? null)
            ->orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        return  $redirects;
    }

    public function getById(Request $request, $id)
    {
        return Redirect::findOrFail($id);
    }

    public function add(Request $request)
    {
        $redirect = RedirectsHelper::addRedirect($request->new_url , $request->old_url, $request);
        if ($redirect)
            LogsHelper::addLogEntry($request, "seo.redirects", "create", $redirect);
    }

    public function updateById(Request $request, $id)
    {
        if(!RedirectsHelper::validateUrls($request->new_url , $request->old_url, $isUpdate=true)) return;

        $old_redirect = Redirect::findOrFail($id);
        $redirect = Redirect::findOrFail($id);
        $redirect->old_url = $request->old_url;
        $redirect->new_url = $request->new_url;
        $redirect->code = $request->code;
        $redirect->active = $request->active ? 1 : 0;
        $redirect->save();

        LogsHelper::addLogEntry($request, "seo.redirects", "update", $redirect, $old_redirect);
    }

    public function toggleActiveById(Request $request, $id)
    {
        $old_redirect = Redirect::findOrFail($id);
        $redirect = Redirect::findOrFail($id);
        $redirect->active =  $redirect->active ? 0 : 1;
        $redirect->save();

        LogsHelper::addLogEntry($request, "seo.redirects", "update", $redirect, $old_redirect);
    }

    public function deleteById(Request $request, $id)
    {
        $redirect = Redirect::findOrFail($id);
        $redirect->delete();

        LogsHelper::addLogEntry($request, "seo.redirects", "delete", null, $redirect);
    }

    public function addByFile(Request $request)
    {
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $rows = Excel::toCollection(new RedirectsImport, request()->file('file'));
            if (sizeof($rows) == 0)
                return;
            foreach ($rows[0] as &$column) {
                $_request = (object) [
                    'code' => count($column) > 2 ? $column[2]:'301',
                    'active' => 1,
                ];

                $old_url = $column[0];
                $new_url = $column[1];
                $redirect = RedirectsHelper::addRedirect($new_url , $old_url, $_request);
                if ($redirect)
                    LogsHelper::addLogEntry($request, "seo.redirects", "create", $redirect);
            }
        }
    }


}
