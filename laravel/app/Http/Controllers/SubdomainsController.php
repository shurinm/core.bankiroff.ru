<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Region;

class SubdomainsController extends Controller
{
    public function getByStr(Request $request, $string = null)
    {
        return Region::active()->activeSubdomain()->matchSubdomain($string)->first();
    }

    public function getDataByStr(Request $request, $string = null)
    {
        return Region::matchSubdomain($string)->first();
    }

    public function getAllActive(Request $request)
    {
        return Region::activeSubdomain()->select('name', 'subdomain')->get();
    }
}
